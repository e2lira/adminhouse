
<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

$cakeDescription = 'Kualitet';

?>
<!DOCTYPE html>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $cakeDescription ?>:
        <?= $this->fetch('title') ?>
    </title>

    <?= $this->Html->meta('icon') ?>


    <?= $this->Html->css('bootstrap') ?>
    <?= $this->Html->css('select2') ?>
    <?= $this->Html->css('select2-bootstrap4')?>
    <?= $this->Html->css('minicolors')?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <?= $this->Html->script('jquery.min') ?>
    <?= $this->Html->script('select2.full.min')?>
    <?= $this->Html->script('bootstrap') ?>
    <?= $this->Html->script('minicolors') ?>
    <?= $this->Html->script('minicolors') ?>

    <?= $this->fetch('meta') ?>
    <?php echo $this->CKEditor->loadJs(); ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

    <script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <link rel="stylesheet" href="http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">

</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light">
    <a class="navbar-brand" style="color: #F4F6F7;">Bienvenido:   <label style="color: gold; "><?php print $this->request->session()->read('Auth.User.username'); ?></label></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation" style="color: #FFFFFF;">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
            </li>
            <li class="nav-item">
                 <li class="nav-link"><?= $this->Html->link('Usuarios',['controller' => 'Users', 'action' => 'index'])?></li>
            </li>
            <li class="nav-item">
                <li class="nav-link"><?= $this->Html->link('Condominio',['controller' => 'Condominio', 'action' => 'index'])?></li>
            </li>
            <li class="nav-item">
                <li class="nav-link"><?= $this->Html->link('Salir', ['controller' => 'Users', 'action' => 'logout']) ?></li>
            </li>
        </ul>

        <!--     <div class="top-bar-section">
                 <ul class="right">
                     <li><?= $this->Html->image("../images/mex.png", [
                         'url'=>['controller'=> 'App', 'action'=>'change-language','es']]) ?></li>
                     <li><?= $this->Html->image("../images/usa.png", [
                         'url'=>['controller'=> 'App', 'action'=>'change-language','en_US']]) ?></li>
                 </ul>
             </div> -->
    </div>


</nav>

<?php
	echo $this->Html->script('SweetAlertHelper.sweetalert2.min');
?>

    <?= $this->Flash->render() ?>
    <div class="container-fluid">
        <?= $this->fetch('content') ?>
    </div>


    <footer>
    </footer>



</body>

<style>

    .navbar{
        background-color:  #aaaaaa;
        border-radius: 0;
        border-color: #59150d;
        color: #FFFFFF;
        margin-bottom: 40px;
    }

    .navbar ul li a{
        color: #FFFFFF;
    }

    .card {
        margin-top: 45px;
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
        transition: 0.3s;
        background-color: #F7F9F9;
        color: #d4b60d;

    }

    .card:hover {
        box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2);
    }

    ul{
        list-style:none;
    }



    .azul{
        color: #1A5276;
    }
    td{
        color: #5D6D7E;
    }
    th{
        color: #909497;
    }

    a{
        color: #922B21;
        margin: 3px;
    }

    .btn-xs{
        background-color: transparent;
        color: #922B21;
        margin-top: -5px;
    }
    .btn-default{
        background-color: #e0e0e0;
    }

    .btn-default{
        margin-top: 20px;
        margin-bottom: 20px;
        background-color: #2980B9;
        width: 100px;
        color: #FFFFFF;
    }


    @media (max-width: 900px) {

        .btn-default{
            margin-top: 20px;
            margin-bottom: 20px;
            background-color: #2980B9;
            width: 100%;
            color: #FFFFFF;
        }
    }



</style>


</html>
