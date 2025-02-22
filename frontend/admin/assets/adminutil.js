let fbdlg = null;
function showFb(msg, type) {
    let fbmsgCnt = document.getElementById('entgenaifbdata');
    if (type === 'success') {
        fbdlg.dialog( "option", "dialogClass", "notice-success" );
        fbdlg.dialog( "option", "title", "Success Message" );
    } else {
        fbdlg.dialog( "option", "dialogClass", "notice-error" );
        fbdlg.dialog( "option", "title", "Error Message" );
    }
    fbmsgCnt.innerHTML = msg;
    fbdlg.dialog('open');
}
function hideFb() {
    let fbmsgCnt = document.getElementById('entgenaifbdata');
    if (fbmsgCnt === undefined) return;
    fbmsgCnt.innerHTML = '&nbsp;';
    fbdlg.dialog('close');
}
jQuery(document).ready(function ($) {
    jQuery("#progressbar").progressbar({
        value: false
    }).hide();
    fbdlg = $( "#entgenaifeedback" ).dialog({ autoOpen: false, width: 700, height: 200, classes: {
            "ui-dialog": "ui-corner-all ui-widget-shadow"
        } });
});
