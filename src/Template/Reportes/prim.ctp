<div class="container">
    <div class="card">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 45px;"></i>
        <p style="color: #0f0f0f !important;">
            El reporte será generado con los datos capturados al día de hoy, si te hicieron falta registros puedes continuar capturando.
        </p>
        <div class="buttons">
            <?= $this->Html->link('Regresar', ['controller' => 'Reportes', 'action' => 'index', 'id' => $condominio['id_condominio']], ['class' => 'btn regresar']) ?>

            <?= $this->Form->create(null, ['url' => ['controller' => 'Reportes', 'action' => 'primSave', $condominio['id_condominio']]]) ?>
            <button type="submit" class="btn continuar" id="continuar">Continuar</button>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>


<style>
    /* Estilo para el contenedor */
    .container {
        position: relative;
        width: 100vw;
        height: 100vh;
    }

    /* Estilo para la tarjeta (card) */
    .card {
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        width: 100%;
        margin: 20px; /* Margen para dispositivos pequeños */
        text-align: center;
        position: absolute;
        top: 30%; /* Ajusta este valor para colocar la tarjeta más arriba */
        left: 50%;
        transform: translate(-50%, -40%);
    }

    /* Estilo para los botones */
    .buttons {
        display: flex;
        justify-content: space-between;
        gap: 2px; /* Espacio entre los botones */
    }

    .btn {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn:hover {
        background-color: #0056b3;
    }
    .regresar {
        background-color: #6c757d;
    }

    .regresar:hover {
        background-color: #5a6268;
    }

    /* Media query para pantallas pequeñas */
    @media (max-width: 600px) {
        .card {
            padding: 15px; /* Ajusta el padding para pantallas más pequeñas */
            max-width: 90%; /* Reduce el ancho máximo en pantallas pequeñas */
        }

        .btn {
            font-size: 14px; /* Ajusta el tamaño del texto en pantallas pequeñas */
            padding: 8px 16px;
        }
    }
</style>
