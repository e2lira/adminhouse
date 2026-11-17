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

$cakeDescription = 'Alpa';
?>
<!DOCTYPE html>
<html>
<head>
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <?= $this->Html->script('jquery.min') ?>
    <?= $this->Html->script('select2.full.min')?>
    <?= $this->Html->script('bootstrap') ?>
    <?= $this->Html->script('minicolors') ?>


    <?= $this->fetch('meta') ?>
    <?php echo $this->CKEditor->loadJs(); ?>

</head>
<body>
<br>
<br>
    <?= $this->Flash->render() ?>
    <div class="container-fluid">
        <?= $this->fetch('content') ?>
    </div>


    <footer>
    </footer>



</body>

<style>

    .navbar{
        background-color:  #A93226;
        border-radius: 0;
        border-color: #A93226;
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
        color: #59150d;

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
