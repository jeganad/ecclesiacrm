//
//  This code is under copyright not under MIT Licence
//  copyright   : 2018 Philippe Logel all right reserved not MIT licence
//


  var maxHeight = 230;
  $( window ).resize(function() {   
    //(document.body.clientHeight); n'a pas l'air top
    var hscreen = $(window).height(),
    height = hscreen > maxHeight ? maxHeight : hscreen;
    $('#mon_rectangle').height(height);
  });

  $('.collapse').on('shown.bs.collapse', function(){
      $(this).parent().find(".fa-chevron-down").removeClass("fa-chevron-down").addClass("fa-chevron-up");
  }).on('hidden.bs.collapse', function(){
      $(this).parent().find(".fa-chevron-up").removeClass("fa-chevron-up").addClass("fa-chevron-down");
  });
  
  // for the calendar
  $('body').on('click','.check-calendar', function(){ 
    var calIDs = $(this).data("id");
    var isChecked  = ($(this).is(':checked'))?1:0;
    
    window.CRM.APIRequest({
      method: 'POST',
      path: 'calendar/setckecked',
      data: JSON.stringify({"calIDs":calIDs,"isChecked":isChecked})
    }).done(function(data) {    
      // we reload all the events
      $('#calendar').fullCalendar( 'refetchEvents' );   
    });
  });
  
  $("#manage-all-calendars").click('focus', function (e) {
    alert("manage all calendars");
  });

  $("#manage-all-groups").click('focus', function (e) {
    alert("manage all groups");
  });
  
  $("#manage-all-shared").click('focus', function (e) {
    alert("manage all shared");
  });
  
  
  $("#add-calendar").click('focus', function (e) {
    bootbox.prompt({
      title: i18next.t("Set Calendar Name"),
      inputType: 'text',
      callback: function (title) {
        if (title !== null && title !== '') {
          window.CRM.APIRequest({
            method: 'POST',
            path: 'calendar/new',
            data: JSON.stringify({"title":title})
          }).done(function(data) {             
             addPersonalCalendars();
          });
        }
      }
    });
  });
  
// the add people to calendar

  function addPersonsFromCalendar(calendarId)
  {
      $('#select-share-persons').find('option').remove();
      
      window.CRM.APIRequest({
            method: 'POST',
            path: 'calendar/getinvites',
            data: JSON.stringify({"calIDs": calendarId})
      }).done(function(data) {    
        var elt = document.getElementById("select-share-persons");
        var len = data.length;
      
        for (i=0; i<len; ++i) {
          if (data[i].access == 2) {
            var option = document.createElement("option");

            option.text = i18next.t("[R ]")+" "+data[i].principal.replace("principals/", "");
            option.value = data[i].principal;
        
            elt.appendChild(option);
          } else if (data[i].access == 3) {
            var option = document.createElement("option");

            option.text = i18next.t("[RW]")+" "+data[i].principal.replace("principals/", "");
            option.value = data[i].principal;
        
            elt.appendChild(option);
          }

        }
      });  
  }
  
  function BootboxContentShare(){
    var frm_str = '<h3 style="margin-top:-5px">'+i18next.t("Share your Calendar")+'</h3>'
       + '<div>'
            +'<div class="row div-title">'
              +'<div class="col-md-4">'
              + '<span style="color: red">*</span>' + i18next.t("With") + ":"                    
              +'</div>'
              +'<div class="col-md-8">'
              +'<select size="6" style="width:100%" id="select-share-persons" multiple>'
              +'</select>'
             +'</div>'
            +'</div>'
            +'<div class="row div-title">'
              +'<div class="col-md-4"><span style="color: red">*</span>' + i18next.t("Set Rights") + ":</div>"
              +'<div class="col-md-8">'
                +'<select name="person-group-Id" id="person-group-rights" class="form-control input-sm"'
                    +'style="width:100%" data-placeholder="text to place">'
                    +'<option value="0">'+i18next.t("Select your rights [R ] or [RW]")+' -- </option>'
                    +'<option value="1">'+i18next.t("[R ]")+' -- </option>'
                    +'<option value="2">'+i18next.t("[RW]")+' -- </option>'
                +'</select>'
              +'</div>'
            +'</div>'
            +'<div class="row div-title">'
              +'<div class="col-md-4"><span style="color: red">*</span>' + i18next.t("Send email notification") + ":</div>"
              +'<div class="col-md-8">'
                +'<input id="sendEmail" type="checkbox">'
              +'</div>'
            +'</div>'            
            +'<div class="row div-title">'
              +'<div class="col-md-4"><span style="color: red">*</span>' + i18next.t("Add persons/Family/groups") + ":</div>"
              +'<div class="col-md-8">'
                +'<select name="person-group-Id" id="person-group-Id" class="form-control select2"'
                    +'style="width:100%">'
                +'</select>'
              +'</div>'
            +'</div>'
          +'</div>';
          
          var object = $('<div/>').html(frm_str).contents();

        return object
  }
  
  function createShareWindow (calIDs)
  {
    var modal = bootbox.dialog({
       message: BootboxContentShare(),
       buttons: [
        {
         label: i18next.t("Delete"),
         className: "btn btn-warning",
         callback: function() {                        
            bootbox.confirm(i18next.t("Are you sure, you want to delete this Share ?"), function(result){ 
              if (result) {
                $('#select-share-persons :selected').each(function(i, sel){ 
                  var principal = $(sel).val();
                  
                  window.CRM.APIRequest({
                     method: 'POST',
                     path: 'calendar/sharedelete',
                     data: JSON.stringify({"calIDs":calIDs,"principal": principal})
                  }).done(function(data) {
                    $("#select-share-persons option[value='"+principal+"']").remove(); 
                    $('#calendar').fullCalendar( 'refetchEvents' );   
                  });
                });
              }
            });
            return false;
         }
        },
        {
         label: i18next.t("Stop sharing"),
         className: "btn btn-danger",
         callback: function() {
          bootbox.confirm(i18next.t("Are you sure, you want to stop sharing your document ?"), function(result){ 
            if (result) {
              window.CRM.APIRequest({
                 method: 'POST',
                 path: 'calendar/sharestop',
                 data: JSON.stringify({"calIDs":calIDs})
              }).done(function(data) {
                addPersonsFromCalendar(calIDs);
                modal.modal("hide");
              });
            }
          });
          return false;
         }
        },
        {
         label: i18next.t("Ok"),
         className: "btn btn-primary",
         callback: function() {               
           modal.modal("hide");
           return true;
         }
        },
       ],
       show: false,
       onEscape: function() {
          modal.modal("hide");
       }
     });
     
     $("#person-group-Id").select2({ 
        language: window.CRM.shortLocale,
        minimumInputLength: 2,
        placeholder: " -- "+i18next.t("Person or Family or Group")+" -- ",
        allowClear: true, // This is for clear get the clear button if wanted 
        ajax: {
            url: function (params){
              return window.CRM.root + "/api/people/search/" + params.term;
            },
            dataType: 'json',
            delay: 250,
            data: "",
            processResults: function (data, params) {
              return {results: data};
            },
            cache: true
        }
      });
      
     $("#person-group-rights").change(function() {
       var rightAccess = $(this).val();
       var deferreds = [];
       var i = 0;
       
       $('#select-share-persons :selected').each(function(i, sel){ 
          var principal = $(sel).val();
          var str = $(sel).text();
          
          deferreds.push(          
            window.CRM.APIRequest({
               method: 'POST',
               path: 'calendar/setrights',
               data: JSON.stringify({"calIDs":calIDs,"principal": principal,"rightAccess":rightAccess})
            }).done(function(data) {
              if (rightAccess == 1) {
                res = str.replace('[RW]', '[R ]');
              } else {
                res = str.replace('[R]', '[RW]');
              }
            
              var elt = [principal,res];
              deferreds[i++] = elt;
            })
          );
          
        });
        
        $.when.apply($, deferreds).done(function(data) {
         // all images are now prefetched
         addPersonsFromCalendar(calIDs);
         
         deferreds.forEach(function(element) {
           $('#select-share-persons option[value="'+element[0]+'"]').text(element[1]);
         }); 
        });
     });
     
     $("#select-share-persons").change(function() {
       $("#person-group-rights").val(0);
     });
          
      
     $("#person-group-Id").on("select2:select",function (e) { 
       var notification = ($("#sendEmail").is(':checked'))?1:0;
       
       if (e.params.data.personID !== undefined) {
           window.CRM.APIRequest({
                method: 'POST',
                path: 'calendar/shareperson',
                data: JSON.stringify({"calIDs":calIDs,"personID": e.params.data.personID,"notification":notification})
           }).done(function(data) { 
             addPersonsFromCalendar(calIDs);
           });
        } else if (e.params.data.groupID !== undefined) {
           window.CRM.APIRequest({
                method: 'POST',
                path: 'calendar/sharegroup',
                data: JSON.stringify({"calIDs":calIDs,"groupID": e.params.data.groupID,"notification":notification})
           }).done(function(data) { 
             addPersonsFromCalendar(calIDs);
           });
        } else if (e.params.data.familyID !== undefined) {
           window.CRM.APIRequest({
                method: 'POST',
                path: 'calendar/sharefamily',
                data: JSON.stringify({"calIDs":calIDs,"familyID": e.params.data.familyID,"notification":notification})
           }).done(function(data) { 
             addPersonsFromCalendar(calIDs);
           });
        }
     });
     
     addPersonsFromCalendar(calIDs);
     modal.modal('show');
     
    // this will ensure that image and table can be focused
    $(document).on('focusin', function(e) {e.stopImmediatePropagation();});  
  }
  
  $('body').on('click','#manage-cal-group', function(){ 
    var calIDs = $(this).data("id");
    var type   = $(this).data("type");
    
    window.CRM.APIRequest({
       method: 'POST',
       path: 'calendar/info',
       data: JSON.stringify({"calIDs":calIDs})
    }).done(function(data) {             
        var allButtons = {};
    
        var buttonDelete = {
          delete: {
            label: i18next.t("Delete"),
            className: 'btn-danger',
            callback: function(){  
                                    
              bootbox.confirm({
                title:i18next.t("Are you sure?"),
                message: i18next.t("You'll lose the calendar, the events and all the share calendars too. This cannot be undone."),
                callback: function(result) {
                  if (result) {
                     window.CRM.APIRequest({
                          method: 'POST',
                          path: 'calendar/delete',
                          data: JSON.stringify({"calIDs":calIDs})
                     }).done(function(data) { 
                       addPersonalCalendars(calIDs);
                       $('#calendar').fullCalendar( 'refetchEvents' );   
                     });
                  }
                }
              });
            }
           }
        };
    
        var buttonManage = {
          manage: {
              label: i18next.t("Manage"),
              className: 'btn-info',
              callback: function(){
                  createShareWindow (calIDs);
                  return true;
              }
          }
        };
        
        var buttonOk = {
          Ok: {
              label: i18next.t("Ok"),
              className: 'btn-primary',
              callback: function(){
                  return true;
              }
          }
        };
    
        if (type == "personal") {
          allButtons = $.extend(allButtons,buttonDelete,buttonManage,buttonOk);
        } else if (type == "group" && data.isAdmin == true) {
          allButtons = $.extend(allButtons,buttonManage,buttonOk);
        } else if (type == "group" && data.isAdmin == false) {
          allButtons = $.extend(allButtons,buttonOk);
        } else if (type == "shared") {
          allButtons = $.extend(allButtons,buttonOk);
        }
    
    
        var dialog = bootbox.dialog({
          title: i18next.t("Calendar Management for")+" : "+data.title,
          message: i18next.t(data.message),
          buttons: allButtons
        });    
    });
    
  });
  
  
// end off add people to calendar
//

  
  $('body').on('click','.editCalendarName', function(){ 
    var calIDs = $(this).data("id");
    var name   = $(this).text();
    
    bootbox.prompt({
      title: i18next.t("Modify Calendar Name"),
      inputType: 'text',
      value:name,
      callback: function (title) {
        if (title !== null && title != '') {
          window.CRM.APIRequest({
            method: 'POST',
            path: 'calendar/modifyname',
            data: JSON.stringify({"title":title,"calIDs":calIDs})
          }).done(function(data) {             
             addPersonalCalendars();
          });
        }
      }
    });    
  });    
  
  $('body').on('click','.editGroupName', function(){ 
    var calIDs = $(this).data("id");
    var name   = $(this).text();
    
    bootbox.prompt({
      title: i18next.t("Modify Group Name"),
      inputType: 'text',
      value:name,
      callback: function (title) {
        if (title !== null && title != '') {
          window.CRM.APIRequest({
            method: 'POST',
            path: 'calendar/modifyname',
            data: JSON.stringify({"title":title,"calIDs":calIDs})
          }).done(function(data) {             
             addGroupCalendars();
          });
        }
      }
    });     
  }); 
  

  $('body').on('click','.editShareName', function(){ 
    var calIDs = $(this).data("id");
    var name   = $(this).text();
    
    bootbox.prompt({
      title: i18next.t("Modify Share Name"),
      inputType: 'text',
      value:name,
      callback: function (title) {
        if (title !== null && title != '') {
          window.CRM.APIRequest({
            method: 'POST',
            path: 'calendar/modifyname',
            data: JSON.stringify({"title":title,"calIDs":calIDs})
          }).done(function(data) {             
             addShareCalendars();
          });
        }
      }
    });      
  }); 
  

  function addPersonalCalendars()
  {
    $('#cal-list').empty();
    
    window.CRM.APIRequest({
      method: 'POST',
      path: 'calendar/getallforuser',
      data: JSON.stringify({"type":"personal","onlyvisible":false})
    }).done(function(data) {    
      var len = data.length;
      
      for (i=0; i<len; ++i) {
        $('#cal-list').append('<li class="list-group-item" style="cursor: pointer;"><div class="input-group my-colorpicker-global my-colorpicker1'+i+' colorpicker-element" data-id="'+data[i].calendarID+'"><input id="checkBox" type="checkbox" class="check-calendar" data-id="'+data[i].calendarID+'"'+((data[i].visible)?"checked":"")+'><i class="fa pull-right fa-gear"  style="font-size: 1.2em" style="color:gray;padding-right:10px;" id="manage-cal-group" data-type="personal" data-id="'+data[i].calendarID+'"></i> <span class="editCalendarName"  data-id="'+data[i].calendarID+'">'+data[i].calendarName+'</span><div class="input-group-addon" style="border: 2;padding:1px 1px;"><i style="background-color:'+data[i].calendarColor+';"></i></li>');
        $(".my-colorpicker1"+i).colorpicker({
          color:data[i].calendarColor,
          inline:false,
          horizontal:true,
          right:true
        });
      }      
    });  
  }


  function addGroupCalendars()
  {
    $('#group-list').empty();
    
    window.CRM.APIRequest({
      method: 'POST',
      path: 'calendar/getallforuser',
      data: JSON.stringify({"type":"group","onlyvisible":false})
    }).done(function(data) {    
      var len = data.length;
      
      for (i=0; i<len; ++i) {
        $('#group-list').append('<li class="list-group-item" style="cursor: pointer;"><div class="input-group my-colorpicker-global my-colorpicker1'+i+' colorpicker-element" data-id="'+data[i].calendarID+'"><input id="checkBox" type="checkbox" class="check-calendar" data-id="'+data[i].calendarID+'"'+((data[i].visible)?"checked":"")+'><i class="fa pull-right fa-gear"  style="font-size: 1.2em" style="color:gray;padding-right:10px;" id="manage-cal-group" data-type="group" data-id="'+data[i].calendarID+'"></i> <span class="editGroupName"  data-id="'+data[i].calendarID+'">'+data[i].calendarName+'</span><div class="input-group-addon" style="border: 2;padding:1px 1px;"><i style="background-color:'+data[i].calendarColor+';"></i></li>');
        
        $(".my-colorpicker1"+i).colorpicker({
          color:data[i].calendarColor,          
          inline:false,
          horizontal:true,
          right:true
        });
      }      
    });  
  }
  
  function addShareCalendars()
  {
    $('#share-list').empty();
    
    window.CRM.APIRequest({
      method: 'POST',
      path: 'calendar/getallforuser',
      data: JSON.stringify({"type":"share","onlyvisible":false})
    }).done(function(data) {    
      var len = data.length;
      
      for (i=0; i<len; ++i) {
        $('#share-list').append('<li class="list-group-item" style="cursor: pointer;"><div class="input-group my-colorpicker-global my-colorpicker1'+i+' colorpicker-element" data-id="'+data[i].calendarID+'"><input id="checkBox" type="checkbox" class="check-calendar" data-id="'+data[i].calendarID+'"'+((data[i].visible)?"checked":"")+'><i class="fa pull-right fa-gear"  style="font-size: 1.2em" style="color:gray;padding-right:10px;" id="manage-cal-group" data-type="shared" data-id="'+data[i].calendarID+'"></i> <span class="editShareName"  data-id="'+data[i].calendarID+'">'+data[i].calendarName+'</span><div class="input-group-addon" style="border: 2;padding:1px 1px;"><i style="background-color:'+data[i].calendarColor+';"></i></li>');
        
        $(".my-colorpicker1"+i).colorpicker({
          color:data[i].calendarColor,
          inline:false,
          horizontal:true,
          right:true
        });
      }      
    });  
  }
  
  $('body').colorpicker().on('changeColor','.my-colorpicker-global', function(e){
    var calIDs = $(this).data("id");
    var color = $(this).data('colorpicker').color.toHex();//.toString('hex');
          
    window.CRM.APIRequest({
      method: 'POST',
      path: 'calendar/setcolor',
      data: JSON.stringify({"calIDs":calIDs,"color":color})
    }).done(function(data) {    
      // we reload all the events
      $('#calendar').fullCalendar( 'refetchEvents' );   
    });
  });
  
  // Add all the calendars
  addPersonalCalendars();  
  addGroupCalendars();
  addShareCalendars();