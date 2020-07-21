var userRole;
var userPermission;
var teamLeads = "";
var members;

var page = {
    init: function(settings) {
        
        page.config = {
            bodyView: settings.bodyView
        };
        $.extend(page.config, settings);
        
        page.config.mainUrl = page.config.baseUrl + "/user-management";
        
        this.getResults();

        //initialize pagination
        page.config.bodyView.on("click",".page-link",function(e) {
        	e.preventDefault();
        	page.getResults($(this).attr("href"));
        });

        page.config.bodyView.on("click",".btn-search-action",function(e) {
            e.preventDefault();
            page.getResults();
        });

        page.config.bodyView.on("click",".btn-add-action",function(e) {
            e.preventDefault();
            page.createRecord();
        });

        $(".common-modal").on("click",".submit-goal",function() {
            page.submitSolution($(this));
        });

        // delete product templates
        page.config.bodyView.on("click",".btn-delete-template",function(e) {
            if(!confirm("Are you sure you want to delete record?")) {
                return false;
            }else {
                page.deleteRecord($(this));
            }
        });

        page.config.bodyView.on("click",".btn-edit-template",function(e) {
            page.editRecord($(this));
        });

        page.config.bodyView.on("click",".load-communication-modal",function(e) {
            page.chatListHistory($(this));
        });

        page.config.bodyView.on("click",".load-role-modal",function(e) {
            page.roleModalOpen($(this));
        });

        page.config.bodyView.on("click",".load-permission-modal",function(e) {
            page.permissionModalOpen($(this));
        });

        page.config.bodyView.on("click",".change-activation",function(e) {
            page.userActivate($(this));
        });

        page.config.bodyView.on("click",".load-task-modal",function(e) {
            page.taskHistory($(this));
        });

        page.config.bodyView.on("click",".load-team-add-modal",function(e) {
            page.teamAdd($(this));
        });

        page.config.bodyView.on("click",".load-team-modal",function(e) {
            page.getTeamInfo($(this));
        });


        $(".common-modal").on("click",".submit-role",function() {
            page.submitRole($(this));
        });

        $(".common-modal").on("click",".submit-permission",function() {
            page.submitPermission($(this));
        });

        $(".common-modal").on("click",".submit-team",function() {
            page.submitTeam($(this));
        });

        $(".common-modal").on("click",".edit-team",function() {
            page.submitEditTeam($(this));
        });

        $(".common-modal").on("keyup",".search-role",function() {
            page.roleSearch();
        });

        $(".common-modal").on("keyup",".search-permission",function() {
            page.permissionSearch();
        });

        $(".common-modal").on("keyup",".search-user",function() {
            page.userSearch();
        });

        $(".common-modal").on("click",".open-permission-input",function() {
            page.toggleDropdown();
        });

        $(".common-modal").on("click",".add-permission",function() {
            page.addPermission();
        });
        
    },
    validationRule : function(response) {
         $(document).find("#product-template-from").validate({
            rules: {
                name     : "required",
            },
            messages: {
                name     : "Template name is required",
            }
        })
    },
    loadFirst: function() {
        var _z = {
            url: this.config.mainUrl+"/records",
            method: "get",
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, "showResults");
    },
    getResults: function(href) {
    	var _z = {
            url: (typeof href != "undefined") ? href : this.config.mainUrl+"/records",
            method: "get",
            data : $(".message-search-handler").serialize(),
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, "showResults");
    },
    showResults : function(response) {
        // $.each(response.replies, function (k, v) {
        //     $("#page-view-result .quickComment").append("<option value='" + k + "'>" + v + "</option>");
        // });
        // $(".quickComment").select2({tags: true});
        // console.log(response.replies);
        $("#loading-image").hide();
    	var addProductTpl = $.templates("#template-result-block");
        var tplHtml       = addProductTpl.render(response);

        $(".count-text").html("("+response.total+")");

    	page.config.bodyView.find("#page-view-result").html(tplHtml);

    }
    ,
    deleteRecord : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.mainUrl+ "/"+ele.data("id")+"/delete",
            method: "get",
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, 'deleteResults');
    },
    deleteResults : function(response) {
        if(response.code == 200){
            this.getResults();
            toastr['success']('Message deleted successfully', 'success');
        }else{
            toastr['error']('Oops.something went wrong', 'error');
        }

    },
    createRecord : function(response) {
        var createWebTemplate = $.templates("#template-create-goal");
        var tplHtml = createWebTemplate.render({data:{}});
        
        var common =  $(".common-modal");
            common.find(".modal-dialog").html(tplHtml); 
            common.modal("show");
    },

    editRecord : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.mainUrl+ "/"+ele.data("id")+"/edit",
            method: "get",
        }
        this.sendAjax(_z, 'editResult');
    },

    editResult : function(response) {
        var createWebTemplate = $.templates("#template-create-goal");
        var tplHtml = createWebTemplate.render(response);
        var common =  $(".common-modal");
            common.find(".modal-dialog").html(tplHtml); 
            common.modal("show");
    },

    submitSolution : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.mainUrl + "/save",
            method: "post",
            data : ele.closest("form").serialize(),
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, "saveSite");
    },
    
    assignSelect2 : function () {
        var selectList = $("select.select-searchable");
            if(selectList.length > 0) {
                $.each(selectList,function(k,v){
                    var element = $(v);
                    if(!element.hasClass("select2-hidden-accessible")){
                        element.select2({tags:true,width:"100%"});
                    }
                });
            }
    },
    saveSite : function(response) {
        if(response.code  == 200) {
            page.loadFirst();
            $(".common-modal").modal("hide");
        }else {
            $("#loading-image").hide();
            toastr["error"](response.error,"");
        }
    },
    chatListHistory : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.baseUrl + "/chat-messages/user/"+ele.data("id")+"/loadMoreMessages?limit=1000",
            method: "get",
        }
        this.sendAjax(_z, 'chatListHistoryResult');
    },
    chatListHistoryResult : function(response) {
        var communicationHistoryTemplate = $.templates("#template-communication-history");
        var tplHtml = communicationHistoryTemplate.render(response);
        var common =  $(".common-modal");
            common.find(".modal-dialog").html(tplHtml); 
            common.modal("show");
    },
    taskHistory : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.baseUrl + "/user-management/task/user/"+ele.data("id"),
            method: "get",
        }
        this.sendAjax(_z, 'taskListHistoryResult');
    },
    taskListHistoryResult : function(response) {
        var communicationHistoryTemplate = $.templates("#template-task-history");
        var tplHtml = communicationHistoryTemplate.render(response);
        var common =  $(".common-modal");
            common.find(".modal-dialog").html(tplHtml); 
            common.modal("show");
    },
    teamAdd : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.baseUrl + "/user-management/user/team/"+ele.data("id"),
            method: "get",
        }
        this.sendAjax(_z, 'teamResult');
    },
    teamResult : function(response) {
        var communicationHistoryTemplate = $.templates("#template-team-add");
        var tplHtml = communicationHistoryTemplate.render(response);
        var common =  $(".common-modal");
            common.find(".modal-dialog").html(tplHtml); 
            common.modal("show");
    },
    submitTeam : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.baseUrl + "/user-management/user/team/"+ele.data("id"),
            method: "post",
            data : ele.closest("form").serialize(),
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, "saveTeam");
    },
    saveTeam : function(response) {
        if(response.code  == 200) {
            toastr['success']('Team created successfully', 'success');
            page.loadFirst();
            $(".common-modal").modal("hide");
        }else {
            $("#loading-image").hide();
            toastr["error"](response.error,"");
        }
    },
    getTeamInfo : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.baseUrl + "/user-management/user/teams/"+ele.data("id"),
            method: "get",
        }
        this.sendAjax(_z, 'teamInfoResult');
    },
    teamInfoResult : function(response) {
        console.log(response);
        members = response.team.members;
        var communicationHistoryTemplate = $.templates("#template-team-edit");
        var tplHtml = communicationHistoryTemplate.render(response);
        var common =  $(".common-modal");
            common.find(".modal-dialog").html(tplHtml); 
            common.modal("show");
    },
    submitEditTeam : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.baseUrl + "/user-management/user/teams/"+ele.data("id"),
            method: "post",
            data : ele.closest("form").serialize(),
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, "saveEditTeam");
    },
    saveEditTeam : function(response) {
        if(response.code  == 200) {
            toastr['success']('Team updated successfully', 'success');
            page.loadFirst();
            $(".common-modal").modal("hide");
        }else {
            $("#loading-image").hide();
            toastr["error"](response.error,"");
        }
    },
    permissionModalOpen : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.baseUrl + "/user-management/permission/"+ele.data("id"),
            method: "get",
        }
        this.sendAjax(_z, 'permissionResult');
    },
    permissionResult : function(response) {
        userPermission = response.userPermission;
        var communicationHistoryTemplate = $.templates("#template-add-permission");
        var tplHtml = communicationHistoryTemplate.render(response);
        var common =  $(".common-modal");
            common.find(".modal-dialog").html(tplHtml); 
            common.modal("show");
    },
    submitPermission : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.mainUrl + "/permission/"+ele.data("id"),
            method: "post",
            data : ele.closest("form").serialize(),
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, "savePermission");
    },
    savePermission : function(response) {
        if(response.code  == 200) {
            toastr['success']('Permission updated successfully', 'success');
            page.loadFirst();
            $(".common-modal").modal("hide");
        }else {
            $("#loading-image").hide();
            toastr["error"](response.error,"");
        }
    },
    roleModalOpen : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.baseUrl + "/user-management/role/"+ele.data("id"),
            method: "get",
        }
        this.sendAjax(_z, 'roleResult');
    },
    roleResult : function(response) {
        userRole = response.userRole;
        var communicationHistoryTemplate = $.templates("#template-add-role");
        var tplHtml = communicationHistoryTemplate.render(response);
        var common =  $(".common-modal");
            common.find(".modal-dialog").html(tplHtml); 
            common.modal("show");
    },
    submitRole : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.mainUrl + "/role/"+ele.data("id"),
            method: "post",
            data : ele.closest("form").serialize(),
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, "saveRole");
    },
    saveRole : function(response) {
        if(response.code  == 200) {
            toastr['success']('Role updated successfully', 'success');
            page.loadFirst();
            $(".common-modal").modal("hide");
        }else {
            $("#loading-image").hide();
            toastr["error"](response.error,"");
        }
    },
    roleSearch : function() {
        var input, filter, ul, li, a, i, txtValue;
        input = document.getElementById("myInputRole");
        filter = input.value.toUpperCase();
        ul = document.getElementById("myRole");
        li = ul.getElementsByTagName("li");
        for (i = 0; i < li.length; i++) {
            a = li[i].getElementsByTagName("a")[0];
            txtValue = a.textContent || a.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                li[i].style.display = "";
            } else {
                li[i].style.display = "none";
            }
        }
    },
    permissionSearch : function() {
        var input, filter, ul, li, a, i, txtValue;
        input = document.getElementById("myInput");
        filter = input.value.toUpperCase();
        ul = document.getElementById("myUL");
        li = ul.getElementsByTagName("li");
        for (i = 0; i < li.length; i++) {
            a = li[i].getElementsByTagName("a")[0];
            txtValue = a.textContent || a.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                li[i].style.display = "";
            } else {
                li[i].style.display = "none";
            }
        }
    },
    userSearch : function() {
        var input, filter, ul, li, a, i, txtValue;
        input = document.getElementById("myInput");
        filter = input.value.toUpperCase();
        ul = document.getElementById("myUL");
        li = ul.getElementsByTagName("li");
        for (i = 0; i < li.length; i++) {
            a = li[i].getElementsByTagName("a")[0];
            txtValue = a.textContent || a.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                li[i].style.display = "";
            } else {
                li[i].style.display = "none";
            }
        }
    },
    toggleDropdown : function() {
        if ($('#permission-from').hasClass('hidden')) {
            $('#permission-from').removeClass('hidden');
        } else {
            $('#permission-from').addClass('hidden');
        }
    },
    addPermission : function() {
        var name = $('#name').val();
        var route = $('#route').val();
        if(!name && !route) {
            toastr["error"]('Both field is required',"");
            return;
        }
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.mainUrl + "/add-permission",
            method: "post",
            data : {
                name : name,
                route : route
            }
        }
        this.sendAjax(_z, "permissionAdded");
    },
    permissionAdded : function(response) {
        if(response.code  == 200) {
            toastr['success']('New Permission added successfully', 'success');
            $("#myUL").append('<li style="list-style-type: none;"><a><input type="checkbox" name="permissions[]" value="'+response.permission.id+'"/><strong>'+response.permission.name+'</strong></a></li>');
            $('#name').val('');
            $('#route').val('');
            $('#permission-from').addClass('hidden');
        }
    },
    userActivate : function(ele) {
        var _z = {
            url: (typeof href != "undefined") ? href : this.config.mainUrl + "/"+ele.data("id")+"/activate",
            method: "post",
            data : ele.closest("form").serialize(),
            beforeSend : function() {
                $("#loading-image").show();
            }
        }
        this.sendAjax(_z, "saveActivate");
    },
    saveActivate : function(response) {
        if(response.code  == 200) {
            toastr['success']('Successfully updated', 'success');
            page.loadFirst();
        }else {
            toastr["error"](response.error,"");
        }
    },
  
}


$.extend(page, common);
var template = $.templates("#template-add-role");
$.views.helpers({
    isSelected: function(role) {
        if (Object.values(userRole).indexOf(role) > -1) {
            return 'checked';
         }
         return '';
    }
  }, template);

  var template = $.templates("#template-add-permission");
$.views.helpers({
    isPermissionSelected: function(permission) {
        if (Object.values(userPermission).indexOf(permission) > -1) {
            return 'checked';
         }
         return '';
    }
  }, template);

  var template = $.templates("#template-team-edit");
  $.views.helpers({
    isMemberSelected: function(member) {
          if (Object.values(members).indexOf(member) > -1) {
              return 'checked';
           }
           return '';
      }
    }, template);