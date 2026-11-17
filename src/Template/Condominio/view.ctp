<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Condominio $condominio
 */
?>

<div class="row">

    <div class="col-md-2">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('Editar Condominio'), ['action' => 'edit', $condominio->id_condominio]) ?> </li>
                    <li><?= $this->Form->postLink(__('Eliminar Condominio'), ['action' => 'delete', $condominio->id_condominio], ['confirm' => __('Are you sure you want to delete # {0}?', $condominio->id_condominio)]) ?> </li>
                    <li><?= $this->Html->link(__('Lista de Condominio'), ['action' => 'index']) ?> </li>
                    <li><?= $this->Html->link(__('Nuevo Condominio'), ['action' => 'add']) ?> </li>
                    <li><?= $this->Html->link(__('Pagos'), ['controller' => 'Pago', 'action' => 'add']) ?> </li>
                    <li><?= $this->Html->link(__('Gastos'), ['controller' => 'Gasto', 'action' => 'add', "id" => $condominio->id_condominio]) ?> </li>
                </ul>
            </div>
        </div>
    </div>
            <div class="col-md-10">
                <div class="azul">

                    <h2><i class="fa fa-user-circle"></i> <?= h($condominio->nombre_condominio)?></h2>
                    <table class="table  table-condensed table-responsive">
        <tr>
            <th scope="row"><?= __('Nombre Condominio') ?></th>
            <td><?= h($condominio->nombre_condominio) ?></td>
        </tr>
         <tr>
             <img src="<?php echo '/../files/Condominio/logo/'.$condominio['logo'] ?>" alt="<?php echo '/', $condominio['logo'] ?>" style="width: 300px;">
        </tr>
        <tr>
            <th scope="row"><?= __('Direccion Condominio') ?></th>
            <td><?= h($condominio->direccion_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Banco Cuenta') ?></th>
            <td><?= h($condominio->banco_cuenta) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id Condominio') ?></th>
            <td><?= $this->Number->format($condominio->id_condominio) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Telefono Condominio') ?></th>
            <td><?= h($condominio->telefono_condominio) ?></td>
        </tr>
                <tr>
        </tr>
    </table>
    <div class="related">
        <h4><?= __('Amenidades Relacionadas') ?></h4>
        <?php if (!empty($amenidades)):  ?>
        <ul>
                <?php
                foreach($amenidades as $amenidad){
        echo "<li>Cuenta con: ".$amenidad->amenidad."</li>";
                }
                ?>
            </ul>
        <?php endif; ?>
    </div>

   </div>
  </div>
</div>
