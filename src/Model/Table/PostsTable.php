<?php
/**
 * Created by PhpStorm.
 * User: Dante
 * Date: 2018-12-30
 * Time: 17:31
 */

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Localized\Validation\FrValidation;
use Cake\Validation\Validator;

class PostsTable extends Table
{
    public function validationDefault(Validator $validator)
    {
        $validator->provider('es', FrValidation::class);
        $validator->add('phoneField', 'myCustomRuleNameForPhone', [
            'rule' => 'phone',
            'provider' => 'es'
        ]);
    }
}