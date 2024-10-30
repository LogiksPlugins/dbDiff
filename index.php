<?php
if(!defined('ROOT')) exit('No direct script access allowed');

loadModule("pages");

$toolBar = [
        "refreshUI"=>["icon"=>"<i class='fa fa-refresh'></i>","tips"=>"Recache"],
        
        //"cloneDC"=>["icon"=>"<i class='fa fa-plus'></i>","tips"=>"Clone"],
        //"exportDC"=>["icon"=>"<i class='fa fa-plus'></i>","tips"=>"Export"],
        // ['type'=>"bar"],
        //"trashDC"=>["icon"=>"<i class='fa fa-trash'></i>","tips"=>"Delete"],
        
		"db1"=> ["title"=>"DBKey 1","type"=>"search","align"=>"left", "class"=>"dbfield",],
		"db2"=> ["title"=>"DBKey 2","type"=>"search","align"=>"left", "class"=>"dbfield",],
        "loadDifferences"=>["icon"=>"<i class='fa fa-play'></i>","title"=>"Load Diff"],
        "generateSQLAlterQueries"=>["icon"=>"<i class='fa fa-code'></i>","title"=>"Generate Alter SQL"],
        
        "copyContent"=>["title"=>"Copy","align"=>"right"],    
// 		"panelForms"=>["title"=>"Forms","align"=>"right","class"=>($_REQUEST['panel']=="forms")?"active":""],
// 		"panelInfoviews"=>["title"=>"Infoviews","align"=>"right","class"=>($_REQUEST['panel']=="infoviews")?"active":""],
// 		"panelVisuals"=>["title"=>"Visuals","align"=>"right","class"=>($_REQUEST['panel']=="infovisuals")?"active":""],
// 		"panelViews"=>["title"=>"Views","align"=>"right","class"=>($_REQUEST['panel']=="views")?"active":""],
];

function pageContentArea() {
    //
    return "<div class='mainbox'><div class='col-md-4'><br><textarea id='exclude_textarea' class='form-control textarea' placeholder='Exlcude Tables Filters' ></textarea><textarea id='copyTarget' style='width: 0px;height: 0px;opacity: 0;'></textarea></div><div class='col-md-8'><div id='mainContainer'><h3 align=center><br><br><br>Click scan to view differance between databases</h3></div></div></div>";
}

printPageComponent(false,[
    "toolbar"=>$toolBar,
    //"sidebar"=>"pageSidebar",
    "contentArea"=>"pageContentArea"
  ]);
?>
<style>
.mainbox {
    /*background: red;*/
    height: 95%;
    height: calc(100% - 43px);
    overflow: auto;
}
#exclude_textarea {
    height: 70%;
    resize: none;
}
.pageCompToolBar .navbar-form.dbfield {
    padding-right: 0px;
}
#mainContainer.padded {
    padding: 50px;
    padding-top: 20px;
    height: 100%;
}
#mainContainer.padded pre {
    height: 100%;
}
</style>
<script>
$(function() {
    if(localStorage.getItem("LOGIKS_CMS_DBDIFF_excludes")==null) {
        localStorage.setItem("LOGIKS_CMS_DBDIFF_excludes", "z,z0,z1,z2,z3,z4,z5,temp");
    }
    if(localStorage!=null) {
        $($(".pageCompToolBar .navbar-form.dbfield")[0]).find("input").val(localStorage.getItem("LOGIKS_CMS_DBDIFF_db1"));
        $($(".pageCompToolBar .navbar-form.dbfield")[1]).find("input").val(localStorage.getItem("LOGIKS_CMS_DBDIFF_db2"));
        $("#exclude_textarea").val(localStorage.getItem("LOGIKS_CMS_DBDIFF_excludes"));
    }
});
function refreshUI() {
    window.location.reload();
}
function loadDifferences() {
    if($($(".pageCompToolBar .navbar-form.dbfield")[0]).find("input").val().length<=0) {
        lgksAlert("DB Key 1 Missing");
        return;
    }
    if($($(".pageCompToolBar .navbar-form.dbfield")[1]).find("input").val().length<=0) {
        lgksAlert("DB Key 2 Missing");
        return;
    }
    
    if(localStorage!=null) {
        localStorage.setItem("LOGIKS_CMS_DBDIFF_db1", $($(".pageCompToolBar .navbar-form.dbfield")[0]).find("input").val());
        localStorage.setItem("LOGIKS_CMS_DBDIFF_db2", $($(".pageCompToolBar .navbar-form.dbfield")[1]).find("input").val());
        
        localStorage.setItem("LOGIKS_CMS_DBDIFF_excludes", $("#exclude_textarea").val());
    }
    
    $("#mainContainer").html("<div class='ajaxloading ajaxloading5'></div>");
    $("#mainContainer").load(_service("dbDiff", "dbcompare")+
                    "&db1="+$($(".pageCompToolBar .navbar-form.dbfield")[0]).find("input").val()+
                    "&db2="+$($(".pageCompToolBar .navbar-form.dbfield")[1]).find("input").val()+
                    "&excludes="+$("#exclude_textarea").val(), function() {
            
            $("#mainContainer").addClass("padded");
        });
}

function generateSQLAlterQueries() {
    if($($(".pageCompToolBar .navbar-form.dbfield")[0]).find("input").val().length<=0) {
        lgksAlert("DB Key 1 Missing");
        return;
    }
    if($($(".pageCompToolBar .navbar-form.dbfield")[1]).find("input").val().length<=0) {
        lgksAlert("DB Key 2 Missing");
        return;
    }
    
    if(localStorage!=null) {
        localStorage.setItem("LOGIKS_CMS_DBDIFF_db1", $($(".pageCompToolBar .navbar-form.dbfield")[0]).find("input").val());
        localStorage.setItem("LOGIKS_CMS_DBDIFF_db2", $($(".pageCompToolBar .navbar-form.dbfield")[1]).find("input").val());
        
        localStorage.setItem("LOGIKS_CMS_DBDIFF_excludes", $("#exclude_textarea").val());
    }
    
    $("#mainContainer").html("<div class='ajaxloading ajaxloading5'></div>");
    $("#mainContainer").load(_service("dbDiff", "sqlalterquery")+
                    "&db1="+$($(".pageCompToolBar .navbar-form.dbfield")[0]).find("input").val()+
                    "&db2="+$($(".pageCompToolBar .navbar-form.dbfield")[1]).find("input").val()+
                    "&excludes="+$("#exclude_textarea").val(), function() {
            
            $("#mainContainer").addClass("padded");
        });
}
function copyContent() {
    if($("#mainContainer pre").length<=0) {
        lgksToast("Nothing to copy yet");
        return;
    }
    
    $("#copyTarget").val($("#mainContainer pre").text());
    var copyText = document.getElementById("copyTarget");

    /* Select the text field */
    copyText.select();
    copyText.setSelectionRange(0, 99999); /* For mobile devices */
    
    /* Copy the text inside the text field */
    document.execCommand("copy");
    
    lgksToast("Content copied to memory");
}
</script>