<?= $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.css') ?>
<?= $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/css/bootstrap.css') ?>

<?= $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js') ?>
<?= $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js') ?>
<?= $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.js') ?>
<?= $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.js') ?>

<script>
  $(document).ready(function() {
   var calendar = $('#calendar').fullCalendar({
     editable:true,
     header:{
     left:'prev,next today',
     center:'title',
     right:'month,agendaWeek,agendaDay,listWeek',
       views: {
        listDay: { buttonText: 'list Day' },
        listWeek: { buttonText: 'list Week' }
      },
    },
    events: {
            url: '/calendario/load',
            type: 'GET',
            dataType: 'json',
            success: function(result) {
                var events = [];
                $.each(result, function(index,obj){
                //alert("Indice: " + index + " , titulo: " + obj.title );
                  startDay = moment(obj.start).format("Y-MM-DD HH:mm:ss");
                  endDay = moment(obj.end).format("Y-MM-DD HH:mm:ss");
                    events.push({
                         title: obj.title,
                         start: startDay, // will be parsed
                         end: endDay
                   });
                });
            }
    },
    selectable:true,
    selectHelper:true,
    select: function(start, end, allDay)
    {
      var title = prompt("Enter Event Title");
     if(title)
     {
      var start = $.fullCalendar.formatDate(start, "Y-MM-DD HH:mm:ss");
      var end = $.fullCalendar.formatDate(end, "Y-MM-DD HH:mm:ss");
      $.ajax({
       url:"insert.php",
       type:"POST",
       data:{title:title, start:start, end:end},
       success:function()
       {
        calendar.fullCalendar('refetchEvents');
        alert("Added Successfully");
       }
      })
     }
    },
    editable:true,
    eventResize:function(event)
    {
     var start = $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm:ss");
     var end = $.fullCalendar.formatDate(event.end, "Y-MM-DD HH:mm:ss");
     var title = event.title;
     var id = event.id;
     $.ajax({
      url:"/calendar/update.php",
      type:"POST",
      data:{title:title, start:start, end:end, id:id},
      success:function(){
       calendar.fullCalendar('refetchEvents');
       swal("Complete!", "Event Updated", "success");
      }
     })
    },

    eventDrop:function(event)
    {

     var start = $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm:ss");
     var end = $.fullCalendar.formatDate(event.end, "Y-MM-DD HH:mm:ss");
     var title = event.title;
     var id = event.id;
     $.ajax({
      url:"/calendar/update.php",
      type:"POST",
      data:{title:title, start:start, end:end, id:id},
      success:function()
      {
       calendar.fullCalendar('refetchEvents');
       swal("Complete!", "Event Updated", "success");
      }
     });
    },

    eventClick:function(event)
    {
     swal({
  text: "Are you sure you want to DELETE this event?",
  icon: "warning",
  buttons: true,
  dangerMode: false,
})
.then(function (isConfirm){
  if(isConfirm){
var id = event.id;
      $.ajax({
       url:"/calendar/status.php",
       type:"POST",
       data:{id:id},
       success:function()
       {
        calendar.fullCalendar('refetchEvents');
       }
      })
}

});
    },

   });
  });

  </script>
<br>
<div class="container">
   <div id="calendar"></div>
</div>