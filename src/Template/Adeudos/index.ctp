<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Adeudo[]|\Cake\Collection\CollectionInterface $adeudos
 */
?>

<div class="row">
    <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
        <div class="card">
            <div class="container">
                <h2>Acciones</h2>
            </div>
            <div>
                <ul>
                    <li><?= $this->Html->link(__('regresar'), ['controller'=>'pages','action' => 'tablero']) ?></li>

                </ul>
            </div>
        </div>

        <div class="card">
            <div class="container">
                <h3>Adeudo</h3>
            </div>
            <div>
                <ul>
                    <label><span style="color: #6c757d;">Adeudo actual:</span> <b style="color: #5a6268 !important;"> <?= $this->Number->format($query2['sum'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?></b></label>
                    <br>
                    <label><span style="color: #6c757d;">Saldo a favor:</span> <b style="color: #5a6268 !important;"> <?= $this->Number->format($favor['sum'], [
                                'locale' => 'en_US',
                                'places' => 2,
                                'before' => '$'
                            ]) ?></b></label>
                </ul>
            </div>
        </div>
        <br>
    </div>
    <br>
    <div class="col-xs-12 col-sm-12 col-md-9 ">
        <h3><?= __('Adeudos') ?> </h3><h3></h3>
        <table class="table table-striped table-hover  table-sm" id="tableCond" name="form1">
        <thead>
            <tr>
                <th scope="col"><?= $this->Paginator->sort('departamento') ?></th>
                <th scope="col"><?= $this->Paginator->sort('mes') ?></th>
                <th scope="col"><?= $this->Paginator->sort('concepto') ?></th>
                <th scope="col"><?= $this->Paginator->sort('monto') ?></th>
                <th scope="col">Aplicar</th>
                <!-- <th scope="col">
                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#exampleModal" id="aplicar" >
                        Aplicar
                    </button></th>
            </tr> -->
        </thead>
        <tbody name="form1">
            <?php foreach ($adeudos as $adeudo): ?>
            <tr>
                <td><?= h($adeudo['departamento']) ?></td>
                <td><?= $this->Time->format($adeudo['mes'],'MMMM y') ?></td>
                <td><?= h($adeudo['concepto']) ?></td>
                <td><?= $this->Number->format($adeudo['monto'], [
                        'locale' => 'en_US',
                        'places' => 2,
                        'before' => '$'
                    ]) ?></td>
                <td>
                    <input type="radio" name="radioButton<?= $adeudo['id_adeudo'] ?>" value="2" data-id-orden="<?= $adeudo['id_adeudo'] ?>" id="rad" <?php if($favor->abono != 0){echo "enable";}else{echo"disable"; } ?> >
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

        <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('primero')) ?>
            <?= $this->Paginator->prev('< ' . __('anterior')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('siguiente') . ' >') ?>
            <?= $this->Paginator->last(__('ultimo') . ' >>') ?>
        </ul>
        <p><?= $this->Paginator->counter(['format' => __('Pagina {{page}} de {{pages}}, mostrando {{current}} registro(s) de {{count}} total')]) ?></p>
    </div>
  </div>
</div>
<!-- Modal
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Aplicar pagos a:</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" onclick="location.reload();">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="checkid"></p>
                <br>
                <input type="text" id="sum" value="sum" hidden>


            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="location.reload();">Cerrar</button>
                <button type="button" class="btn btn-primary" id="enviar">Enviar</button>
            </div>
        </div>
    </div>
</div>-->

<script>
    $(document).ready(function(){
        $('#tableCond input[type="radio"]').each(function (index, element) {
            $(element).on('click', function (event) {
                var radio = $(this);
                var idOrden = radio.attr('data-id-orden');
                //$(event.currentTarget).find('input[id="abrir"]').attr('data-id');

                var radioValue = $('input[name="radioButton'+idOrden+'"]:checked').val();
                console.log('Abrir orden ' + idOrden);
                $.ajax({
                    method: "get",
                    url: '/adeudos/actualizaSaldo/' + idOrden + '/' + radioValue,
                    dataType: 'json',
                    success: function(data) {
                        console.log(data.message);
                        console.log('Regreso de AJAX');
                    }
                });
            });
        });



        $('#tableCond input[type="radio"]').each(function (index, element) { //index: N. objetos dentro del table, element: objeto que se esta buscando
            $(element).on('click', function (event) {
                var radio = $(this);
                var idOrden = radio.attr('data-id-orden');
                //$(event.currentTarget).find('input[id="abrir"]').attr('data-id');
                console.log('Algo pa que sepas event ' + event);
                var radioValue = $('input[name="radioButton'+idOrden+'"]:checked').val();
                console.log('Abrir orden ' + idOrden);
                $.ajax({
                    method: "get",
                    url: window.location.reload(),
                    dataType: 'json',
                    success: function(data) {
                        console.log(data.message);
                    }
                });
            });
        });


    });
</script>
