<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Supervision $supervision
 */
$this->loadHelper('Time');

?>


<?php foreach ($area as $area): ?>
    <div class="row">
        <div class="col-md-2 col-lg-2">
            <img src="/files/Areas/imagen/<?= $area->imagen ?>" alt="Area Image" class="img-thumbnail" data-toggle="modal" data-target="#imageModal<?= $area->id_area ?>" style="cursor: pointer;">
        </div>
        <div class="col-md-1 col-lg-2">
            <h4><?= $area->concepto ?></h4>
            <label><span style="color: #3c4654;"><b>Estado: </b> </span> <?= $area->estado?></label>
            <br>
            <label><span style="color: #3c4654;"><b>Observaciones: </b> </span></label>
            <p><?= $area->observaciones ?></p>
            <label><span style="font-size: 8px;"><?= $this->Time->format($area->fecha, 'dd MMMM yyyy') ?></span></label>
        </div>
    </div>
    <br>
    <!-- Modal -->
    <div class="modal fade" id="imageModal<?= $area->id_area ?>" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel<?= $area->id_area ?>" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img src="/files/Areas/imagen/<?= $area->imagen ?>" alt="Area Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

<?php endforeach; ?>
