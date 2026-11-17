<div class="jumbotron">
    <h1 class="display-4">Recibos del mes</h1>
    <p class="lead">A continuación prodra generar todos los recibos generados en el mes</p>
    <hr class="my-4">
    <p>Seleccione la fecha para generar los recibos o visualiza los reportes generados</p>
    <p class="lead">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
            Generar reporte
        </button>
        <a href="/Reportes/lista/<?= h($condominio->id_condominio)?>" class="btn btn-primary"> Lista de reportes</b></a>

    </p>
</div>


    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Recibos por mes</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="recibosPago form large-9 medium-8 columns content">
                        <?= $this->Form->create($recibosPago) ?>
                        <fieldset>
                            <?php
                            echo $this->Form->control('id_condominio',['type' => 'hidden', 'value'=>$condominio->id_condominio]);
                            echo $this->Form->control('fecha');
                            ?>
                        </fieldset>
                        <?= $this->Form->button(__('Submit')) ?>
                        <?= $this->Form->end() ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
