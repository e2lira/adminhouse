<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Condominio $condominio
 */
?>
<?= $this->Html->script('upload3') ?>
<?= $this->Html->script('ShowImagePreview') ?>
<div class="row">
    <div class="col-md-3 col-sm-12 col-xs-12 col-lg-3" id="actions-sidebar">
        <ul class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul class="side-nav">
                    <li class="heading"><?= __('Actions') ?></li>
                    <li><?= $this->Form->postLink(
                        __('Delete'),
                        ['action' => 'delete', $condominio->id_condominio],
                        ['confirm' => __('Are you sure you want to delete # {0}?', $condominio->id_condominio)]
                        )
                        ?></li>
                    <li><?= $this->Html->link(__('List Condominio'), ['action' => 'index']) ?></li>
                </ul>
            </div>
        </ul>
    </div>
    <div class="col-md-9 col-sm-12 col-xs-12 col-lg-9">
        <?= $this->Form->create($condominio,['enctype' => 'multipart/form-data']) ?>

        <fieldset>
            <legend><?= __('Edit Condominio') ?></legend>
            <?php
            echo $this->Form->control('nombre_condominio');
            echo $this->Form->input('logo', ['type' => 'file', 'class'=>'multi with-preview']);
            echo $this->Form->control('direccion_condominio');
            echo $this->Form->control('telefono_condominio');
            /* echo $this->Form->control('amenidad._ids', ['type'=> 'select',
             'options' => $amenidades,
             'empty'=> 'selecciona',
             'multiple'=>'multiple']); */
            echo $this->Form->control('rfc',['value'=>'RFC: ']);
            ?>
        </fieldset>
        <?= $this->Form->button(__('Submit')) ?>
        <?= $this->Form->end() ?>
    </div>
</div>

<script>
    $(document).ready(function () {
        console.log("Aqui ando");
        $('#amenidad-ids').select2({
            cache: true,
            serverSide: true,
            placeholder:'Selecciona una amenidad',
            dropdownAutoWidth: true,
            multiple: true,
            theme: "bootstrap4",
            ajax: {
                url:  '/condominio/getAmenidades',
                dataType: 'json',
                data: function(params) {
                    var query={
                        search: params.term,
                        page: params.page || 1,
                        size: '5'
                    }
                    return query;
                }
            }
        });
    });
</script>
