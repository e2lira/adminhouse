<!-- Section: Design Block -->
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<script type="text/javascript" src="http://static.runoob.com/assets/qrcode/qrcode.min.js"></script>
<script type="text/javascript">
    function generateBarCode()
    {
        var nric = $('#text').val();
        var url = 'https://api.qrserver.com/v1/create-qr-code/?data=' + nric + '&amp;size=50x50';
        $('#barcode').attr('src', url);
    }
</script>
<section class="">
    <!-- Jumbotron -->
    <div class="px-4 py-5 px-md-5 text-center text-lg-start" style="background-color: hsl(0, 0%, 96%)">
        <div class="container">
            <div class="row gx-lg-5 align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h1 class="my-5 display-3 fw-bold ls-tight">
                        Datos de la visita <br />
                        <span class="text-primary">¡Tu visita fue registrada!</span>
                    </h1>
                    <center>
                        <div id="qrcode" style="height:150px;width:150px;" v-loading="PanoramaInfo.bgenerateing"></div>
                    </center>

                </div>

                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="card">
                        <div class="card-body py-5 px-md-5">

                            <?php
                            /**
                             * @var \App\View\AppView $this
                             * @var \App\Model\Entity\Visita $visita
                             */
                            ?>
                            <div class="visitas view large-9 medium-8 columns content">

                                <center>

                                    <table class="vertical-table">
                                        <tr>
                                            <th scope="row"><?= __('Nombre') ?></th>
                                            <td><?= h($visita->nombre) ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?= __('Departamento') ?></th>
                                            <td><?= h($visita->departamento) ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?= __('Tipo Visita') ?></th>
                                            <td><?= h($visita->tipo_visita) ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?= __('Auto') ?></th>
                                            <td><?= h($visita->auto) ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?= __('Color') ?></th>
                                            <td><?= h($visita->color) ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?= __('Marca') ?></th>
                                            <td><?= h($visita->marca) ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?= __('Placas') ?></th>
                                            <td><?= h($visita->placas) ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?= __('Fecha') ?></th>
                                            <td><?= h($visita->fecha) ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?= __('Hora') ?></th>
                                            <td><?= h($visita->hora) ?></td>
                                        </tr>
                                    </table>
                            </div>
                            </center>



                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Jumbotron -->
</section>
<!-- Section: Design Block -->
<script>
    function downloadURI(uri, name) {
        var link = document.createElement("a");
        link.download = name;
        link.href = uri;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        delete link;
    };

    window.onload = function ()
    {
        console.log('onload');
        let qrcode = new QRCode(document.getElementById("qrcode"),
            {
                text: "kualitet.mx/visitas/" + <?= h($visita->id_visita) ?>,
                width: 150,
                height: 150,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        setTimeout(
            function ()
            {
                let dataUrl = document.querySelector('#qrcode').querySelector('img').src;
                downloadURI(dataUrl, 'qrcode.png');
            }
            ,1000);

    };

</script>
