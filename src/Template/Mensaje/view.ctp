<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Mensaje $mensaje
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
                <li><?= $this->Form->postLink(__('Eliminar Mensaje'), ['action' => 'delete', $mensaje->id_mensaje], ['confirm' => __('Are you sure you want to delete # {0}?', $mensaje->id_mensaje)]) ?> </li>
                <li><?= $this->Html->link(__('Lista de Mensaje'), ['action' => 'index']) ?> </li>
            </ul>
        </div>
    </div>
    <br>
   <!-- <button type="button" class="btn btn-info" data-toggle="modal" data-target="#exampleModal">
        Marcar como leido
    </button>
   -->

</div>
<br>
 <div class="Conversacion" style="width: 40%; background-color: #b9bbbe; border-radius: 15px;">
    <div class="row" style="margin: 15px; color: white;">
        <div>
            <label><?= h($mensaje->titulo) ?></label>
            <?= $this->Text->autoParagraph(h($mensaje->mensaje)); ?>
        </div>
        <br>
    </div>
     <div class="row" style="margin: 15px; color: white;">
         <div class="col-md-9">
             <label><?= h($mensaje->adjunto) ?></label>
         </div>
         <div class="col-md-3">
             <td><?= $this->Time->format($mensaje['fecha'],'d MMMM Y H:mm') ?></td>
         </div>
     </div>
 </div>

    <!-- Modal
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ¿Estas seguro de marcar como leído este mensaje?
                    <?= $this->Form->create($mensaje) ?>
                    <fieldset>
                        <?php
                        echo $this->Form->control('estatus',['value'=>1, 'type'=>'hidden']);
                        ?>
                    </fieldset>
                    <?= $this->Form->button(__('Si')) ?>
                    <?= $this->Form->end() ?>
                </div>
                <div class="modal-footer">
                </div>
            </div>
        </div>
    </div> -->

    <div id="body">

        <div id="chat-circle" class="btn btn-raised">
            <div id="chat-overlay"></div>
            <i class="material-icons" style="color: #4e555b !important;">Responder</i>
        </div>

        <div class="chat-box">
            <div class="chat-box-header">
                Responder mensaje
                <span class="chat-box-toggle"><i class="material-icons">Cerrar</i></span>
            </div>
            <div class="chat-box-body">
                <div class="respuestas form columns content" style="margin: 10px;">
                    <?= $this->Form->create($respuesta,['type' => 'file']) ?>
                    <fieldset>
                        <?php
                        echo $this->Form->control('id_mensaje', ['type'=>'hidden','value'=>$mensaje->id_mensaje] );
                        echo $this->Form->control('mensaje');
                        echo $this->Form->control('adjunto',['type' => 'file']);
                        echo $this->Form->control('id_condominio', ['type'=>'hidden', 'value'=>$mensaje->id_condominio]);
                        echo $this->Form->control('id_condomino', ['type'=>'hidden', 'value'=>$mensaje->id_condomino]);
                        ?>
                    </fieldset>
                    <?= $this->Form->button(__('Submit')) ?>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>




    </div>

    <style>

        #center-text {
            display: flex;
            flex: 1;
            flex-direction:column;
            justify-content: center;
            align-items: center;
            height:100%;

        }
        #chat-circle {
            position: fixed;
            bottom: 50px;
            right: 50px;
            background: #ffeeba;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            color: white;
            padding: 28px;
            cursor: pointer;
            box-shadow: 0px 3px 16px 0px rgba(0, 0, 0, 0.6), 0 3px 1px -2px rgba(0, 0, 0, 0.2), 0 1px 5px 0 rgba(0, 0, 0, 0.12);
        }

        .btn#my-btn {
            background: white;
            padding-top: 13px;
            padding-bottom: 12px;
            border-radius: 45px;
            padding-right: 40px;
            padding-left: 40px;
            color: #5865C3;
        }
        #chat-overlay {
            background: rgba(255,255,255,0.1);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: none;
        }


        .chat-box {
            display:none;
            background: #efefef;
            position:fixed;
            right:30px;
            bottom:50px;
            width:350px;
            max-width: 85vw;
            max-height:100vh;
            border-radius:5px;
            /*   box-shadow: 0px 5px 35px 9px #464a92; */
            box-shadow: 0px 5px 35px 9px #ccc;
        }
        .chat-box-toggle {
            float:right;
            margin-right:15px;
            cursor:pointer;
        }
        .chat-box-header {
            background: #ffeeba;
            height:70px;
            border-top-left-radius:5px;
            border-top-right-radius:5px;
            color: #4e555b;
            text-align:center;
            font-size:20px;
            padding-top:17px;
        }
        .chat-box-body {
            position: relative;
            height:370px;
            height:auto;
            border:1px solid #ccc;
            overflow: hidden;
        }
        .chat-box-body:after {
            content: "";
            background-image: url('https://swall.teahub.io/photos/small/202-2020708_gold-ipad-wallpapers-yellow-polygon-iphone.jpg')
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            height:100%;
            position: absolute;
            z-index: -1;
        }
        #chat-input {
            background: #f4f7f9;
            width:100%;
            position:relative;
            height:47px;
            padding-top:10px;
            padding-right:50px;
            padding-bottom:10px;
            padding-left:15px;
            border:none;
            resize:none;
            outline:none;
            border:1px solid #ccc;
            color:#888;
            border-top:none;
            border-bottom-right-radius:5px;
            border-bottom-left-radius:5px;
            overflow:hidden;
        }
        .chat-input > form {
            margin-bottom: 0;
        }
        #chat-input::-webkit-input-placeholder { /* Chrome/Opera/Safari */
            color: #ccc;
        }
        #chat-input::-moz-placeholder { /* Firefox 19+ */
            color: #ccc;
        }
        #chat-input:-ms-input-placeholder { /* IE 10+ */
            color: #ccc;
        }
        #chat-input:-moz-placeholder { /* Firefox 18- */
            color: #ccc;
        }
        .chat-submit {
            position:absolute;
            bottom:3px;
            right:10px;
            background: transparent;
            box-shadow:none;
            border:none;
            border-radius:50%;
            color:#5A5EB9;
            width:35px;
            height:35px;
        }
        .chat-logs {
            padding:15px;
            height:370px;
            overflow-y:scroll;
        }

        .chat-logs::-webkit-scrollbar-track
        {
            -webkit-box-shadow: inset 0 0 6px rgba(0,0,0,0.3);
            background-color: #F5F5F5;
        }

        .chat-logs::-webkit-scrollbar
        {
            width: 5px;
            background-color: #F5F5F5;
        }

        .chat-logs::-webkit-scrollbar-thumb
        {
            background-color: #5A5EB9;
        }



        @media only screen and (max-width: 500px) {
            .chat-logs {
                height:40vh;
            }
        }

        .chat-msg.user > .msg-avatar img {
            width:45px;
            height:45px;
            border-radius:50%;
            float:left;
            width:15%;
        }
        .chat-msg.self > .msg-avatar img {
            width:45px;
            height:45px;
            border-radius:50%;
            float:right;
            width:15%;
        }
        .cm-msg-text {
            background:white;
            padding:10px 15px 10px 15px;
            color:#666;
            max-width:75%;
            float:left;
            margin-left:10px;
            position:relative;
            margin-bottom:20px;
            border-radius:30px;
        }
        .chat-msg {
            clear:both;
        }
        .chat-msg.self > .cm-msg-text {
            float:right;
            margin-right:10px;
            background: #5A5EB9;
            color:white;
        }
        .cm-msg-button>ul>li {
            list-style:none;
            float:left;
            width:50%;
        }
        .cm-msg-button {
            clear: both;
            margin-bottom: 70px;
        }
    </style>

    <script>
        $(function() {
            var INDEX = 0;
            $("#chat-submit").click(function(e) {
                e.preventDefault();
                var msg = $("#chat-input").val();
                if(msg.trim() == ''){
                    return false;
                }
                generate_message(msg, 'self');
                var buttons = [
                    {
                        name: 'Existing User',
                        value: 'existing'
                    },
                    {
                        name: 'New User',
                        value: 'new'
                    }
                ];
                setTimeout(function() {
                    generate_message(msg, 'user');
                }, 1000)

            })

            function generate_message(msg, type) {
                INDEX++;
                var str="";
                str += "<div id='cm-msg-"+INDEX+"' class=\"chat-msg "+type+"\">";
                str += "          <span class=\"msg-avatar\">";
                str += "            <img src=\"https:\/\/image.crisp.im\/avatar\/operator\/196af8cc-f6ad-4ef7-afd1-c45d5231387c\/240\/?1483361727745\">";
                str += "          <\/span>";
                str += "          <div class=\"cm-msg-text\">";
                str += msg;
                str += "          <\/div>";
                str += "        <\/div>";
                $(".chat-logs").append(str);
                $("#cm-msg-"+INDEX).hide().fadeIn(300);
                if(type == 'self'){
                    $("#chat-input").val('');
                }
                $(".chat-logs").stop().animate({ scrollTop: $(".chat-logs")[0].scrollHeight}, 1000);
            }

            function generate_button_message(msg, buttons){
                /* Buttons should be object array
                  [
                    {
                      name: 'Existing User',
                      value: 'existing'
                    },
                    {
                      name: 'New User',
                      value: 'new'
                    }
                  ]
                */
                INDEX++;
                var btn_obj = buttons.map(function(button) {
                    return  "              <li class=\"button\"><a href=\"javascript:;\" class=\"btn btn-primary chat-btn\" chat-value=\""+button.value+"\">"+button.name+"<\/a><\/li>";
                }).join('');
                var str="";
                str += "<div id='cm-msg-"+INDEX+"' class=\"chat-msg user\">";
                str += "          <span class=\"msg-avatar\">";
                str += "            <img src=\"https:\/\/image.crisp.im\/avatar\/operator\/196af8cc-f6ad-4ef7-afd1-c45d5231387c\/240\/?1483361727745\">";
                str += "          <\/span>";
                str += "          <div class=\"cm-msg-text\">";
                str += msg;
                str += "          <\/div>";
                str += "          <div class=\"cm-msg-button\">";
                str += "            <ul>";
                str += btn_obj;
                str += "            <\/ul>";
                str += "          <\/div>";
                str += "        <\/div>";
                $(".chat-logs").append(str);
                $("#cm-msg-"+INDEX).hide().fadeIn(300);
                $(".chat-logs").stop().animate({ scrollTop: $(".chat-logs")[0].scrollHeight}, 1000);
                $("#chat-input").attr("disabled", true);
            }

            $(document).delegate(".chat-btn", "click", function() {
                var value = $(this).attr("chat-value");
                var name = $(this).html();
                $("#chat-input").attr("disabled", false);
                generate_message(name, 'self');
            })

            $("#chat-circle").click(function() {
                $("#chat-circle").toggle('scale');
                $(".chat-box").toggle('scale');
            })

            $(".chat-box-toggle").click(function() {
                $("#chat-circle").toggle('scale');
                $(".chat-box").toggle('scale');
            })

        })
    </script>
