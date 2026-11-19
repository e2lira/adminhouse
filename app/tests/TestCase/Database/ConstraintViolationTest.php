<?php
declare(strict_types=1);

namespace App\Test\TestCase\Database;

use App\Database\ConstraintViolation;
use Cake\TestSuite\TestCase;
use PDOException;
use RuntimeException;

/**
 * Only the SQLSTATE and the column names of a PostgreSQL constraint error are read, in any server language.
 */
class ConstraintViolationTest extends TestCase
{
    private function driverError(string $state, string $message): PDOException
    {
        $error = new PDOException('driver');
        $error->errorInfo = [$state, 7, $message];

        return $error;
    }

    public function testAnEnglishUniqueViolationNamesTheLastNonDevelopmentColumn(): void
    {
        $violation = ConstraintViolation::from($this->driverError(
            '23505',
            'ERROR: duplicate key value violates unique constraint "x"' . "\n"
                . 'DETAIL: Key (development_id, name)=(1, taken) already exists.',
        ));

        $this->assertNotNull($violation);
        $this->assertSame('_unique', $violation->rule);
        $this->assertSame('name', $violation->field());
    }

    public function testASpanishMessageIsParsedByItsShapeNotByItsWords(): void
    {
        $violation = ConstraintViolation::from($this->driverError(
            '23505',
            'ERROR: llave duplicada viola restricción de unicidad «x»' . "\n"
                . 'DETAIL: Ya existe la llave (development_id, name)=(1, taken).',
        ));

        $this->assertNotNull($violation);
        $this->assertSame('name', $violation->field());
    }

    public function testAForeignKeyViolationBecomesAnExistsInRule(): void
    {
        $violation = ConstraintViolation::from($this->driverError(
            '23503',
            'DETAIL: La llave (development_id, section_id)=(1, 9) no está presente en la tabla «x».',
        ));

        $this->assertNotNull($violation);
        $this->assertSame('_existsIn', $violation->rule);
        $this->assertSame('section_id', $violation->field());
    }

    public function testAnExpressionIndexNamesNoColumn(): void
    {
        $violation = ConstraintViolation::from($this->driverError(
            '23505',
            'DETAIL: Key (development_id, lower(name::text))=(1, taken) already exists.',
        ));

        $this->assertNotNull($violation);
        $this->assertSame('_constraint', $violation->field());
    }

    public function testTheDriverErrorIsFoundInThePreviousChain(): void
    {
        $wrapped = new RuntimeException('wrapped', 0, $this->driverError('23505', 'Key (name)=(x) already exists.'));

        $this->assertSame('name', ConstraintViolation::from($wrapped)?->field());
    }

    public function testOtherErrorsAreNotViolations(): void
    {
        $this->assertNull(ConstraintViolation::from($this->driverError('42P01', 'relation does not exist')));
        $this->assertNull(ConstraintViolation::from(new RuntimeException('plain')));
    }

    public function testRowValuesAreNeverPartOfWhatIsExposed(): void
    {
        $violation = ConstraintViolation::from(
            $this->driverError('23505', 'Key (name)=(secret@example.com) already exists.'),
        );

        $this->assertNotNull($violation);
        $this->assertStringNotContainsString('secret', $violation->field());
    }

    public function testTheConstraintNameIsTheLastQuotedIdentifierOfTheMessage(): void
    {
        // Under row-level security PostgreSQL leaves the DETAIL line out, so the name is all that identifies the key.
        $unique = ConstraintViolation::from($this->driverError(
            '23505',
            'ERROR:  llave duplicada viola restricción de unicidad «units_development_id_number_key»',
        ));
        $foreign = ConstraintViolation::from($this->driverError(
            '23503',
            'ERROR: insert or update on table "units" violates foreign key constraint "units_section_fkey"',
        ));

        $this->assertSame('units_development_id_number_key', $unique?->constraint);
        $this->assertSame('_constraint', $unique->field());
        $this->assertSame('units_section_fkey', $foreign?->constraint);
    }

    public function testAMessageWithoutAQuotedNameHasNoConstraint(): void
    {
        $this->assertNull(ConstraintViolation::from($this->driverError('23505', 'Key (name)=(x) already exists.'))?->constraint);
    }

    public function testColumnsLookedUpByConstraintNameAreUsedForTheField(): void
    {
        $violation = ConstraintViolation::from($this->driverError('23505', 'ERROR: x "k"'));

        $resolved = $violation?->withColumns(['development_id', 'section_id', 'number']);

        $this->assertSame('number', $resolved?->field());
        $this->assertSame('_unique', $resolved->rule);
        $this->assertSame('_constraint', $violation->field());
    }
}
