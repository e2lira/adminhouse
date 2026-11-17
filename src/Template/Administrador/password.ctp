


<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
<style>
    .form-gap {
    padding-top: 70px;
}
body{
    background-color: #f5f5f5;
}
</style>


<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">
<div class="form-gap"></div>
<div class="container">
    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="text-center">
                        <h3><i class="fa fa-lock fa-4x"></i></h3>
                        <h2 class="text-center">Olvido su contraseña?</h2>
                        <p>Introduce tu correo electrónico registrado para recuperar tu contraseña</p>
                        <div class="panel-body">

                            <form id="register-form" role="form" autocomplete="off" class="form" method="post">
                                <?php echo $this->Form->create(); ?>
                                <div class="form-group">
                                    <div class="input-group">
                                        <?php echo $this->Form->input('email', ['autofocus' => true, 'label' => 'Correo electronico', 'required' => true]);
                                        ?>                                    </div>
                                </div>
                                <div class="form-group">
                                    <?php echo $this->Form->button('Enviar'); ?>
                                </div>

                                <input type="hidden" class="hide" name="token" id="token" value="">
                                <?php echo $this->Form->end(); ?>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>