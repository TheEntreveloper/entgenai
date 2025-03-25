const { __, _x, _n, sprintf } = wp.i18n;

let fbdlg = null, provdlg = null;
function showFb(msg, type) {
    let fbmsgCnt = document.getElementById('entgenaifbdata');
    if (type === 'success') {
        fbdlg.dialog( "option", "dialogClass", "notice-success" );
        fbdlg.dialog( "option", "title", __("Success Message", 'entgenai'));
    } else {
        fbdlg.dialog( "option", "dialogClass", "notice-error" );
        fbdlg.dialog( "option", "title", __("Error Message", 'entgenai'));
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
function manProviders(genai_prov_data) {
    let egenaiprovdata = document.getElementById('entgenaiprovlist');
    let html = '<div>';
    if (genai_prov_data !== undefined && genai_prov_data !== null) {
        html += '<ul>';
        for (var prov in genai_prov_data) {
            let hentry = '<li class="top_padded"><a href="#" onclick="showSelProvdata(\'' + prov + '\');">' +prov + '</a></li>';
            html += hentry;
        }
        html += '<li class="top_padded"><a href="#" onclick="showSelProvdata(\''+__('AddNew', 'entgenai')+'\');">Add New</a></li>';
        html += '</ul>';
    } else {
        html += __('No AI Providers found', 'entgenai');
    }
    html+='</div>';
    egenaiprovdata.innerHTML = html;
    provdlg.dialog('open');
}
function populateProvFields(prov, genai_sel_prov, mdls) {
    let models = '';
    if (mdls.length > 0) {
        models = mdls.reduce(function (p, c) {
            return p + ',' + c;
        });
    }
    document.getElementById('entgenai_pvname').value = prov;
    document.getElementById('entgenai_pvurl').value =  genai_sel_prov.url;
    document.getElementById('entgenai_pvkey').value =  genai_sel_prov.apikey;
    document.getElementById('entgenai_pvmdls').value =  models ?? '';
}
function custSubmDisplay(value) {
    document.getElementById('entgenai_custom_subm').style.setProperty('display', value);
}
function customizeSubmission(genai_sel_prov) {
    custSubmDisplay('grid');
    let hdrs = document.getElementById('entgenai_custom_hdr_subm');
    if (genai_sel_prov !== null && genai_sel_prov['headers'] !== null) {
        if ((genai_sel_prov['headers']) instanceof(Object)) {
            hdrs.value = JSON.stringify(genai_sel_prov['headers']);
        } else {
            hdrs.value = genai_sel_prov['headers'];
        }
    } else {
        hdrs.value = '{"content-type":"application/json","Authorization":"Bearer _APIKEY"}';
    }
    let body = document.getElementById('entgenai_custom_body_subm');
    if (genai_sel_prov !== null && genai_sel_prov['body'] !== null) {
        if ((genai_sel_prov['body']) instanceof(Object)) {
            body.value = JSON.stringify(genai_sel_prov['body']);
        } else {
            body.value = genai_sel_prov['body'];
        }
    } else {
        body.value = '{"model":"_LLMODEL", "messages": [\n' +
            '      {\n' +
            '        "role": "developer",\n' +
            '        "content": "_SYSTEM"\n' +
            '      },\n' +
            '      {\n' +
            '        "role": "user",\n' +
            '        "content": "_PROMPT"\n' +
            '      }\n' +
            '    ],\n' +
            '    "stream": _STREAM}';
    }
}

function showSelProvdata(prov) {
    if (prov === __('AddNew', 'entgenai')) {
        let elm = document.getElementById('entgenaiprovdata');
        elm.style.setProperty('display', 'block');
        populateProvFields('', {'url':'','apikey':''}, []);
        customizeSubmission(null);
        return;
    }
    custSubmDisplay('none');
    let genai_sel_prov = genai_prov_data[prov];
    if (genai_sel_prov !== 0 && genai_sel_prov !== null) {
        let elm = document.getElementById('entgenaiprovdata');
        elm.style.setProperty('display', 'block');
        populateProvFields(prov, genai_sel_prov, models[prov]??[]);
        if (['OpenAI', 'Anthropic', 'Gemini', 'local_model'].indexOf(prov) === -1) {
            customizeSubmission(genai_sel_prov);
        }
    }
}
async function updProvider() {
    let updObj = {};
    updObj.prov = document.getElementById('entgenai_pvname').value;
    updObj.url = document.getElementById('entgenai_pvurl').value;
    updObj.apikey = document.getElementById('entgenai_pvkey').value;
    updObj.mdls = document.getElementById('entgenai_pvmdls').value ?? '';
    updObj.mdls = updObj.mdls.split(',').map(function (s) {
        return s.trim();
    });;
    updObj.headers = document.getElementById('entgenai_custom_hdr_subm').value ?? '';
    updObj.headers = JSON.parse(updObj.headers);
    updObj.body = document.getElementById('entgenai_custom_body_subm').value ?? '';
    updObj.body = JSON.parse(updObj.body);
    let jsonResponse = await doFetch('entgenai/v1/save/provider', updObj);
    if (jsonResponse !== undefined && jsonResponse['result'] > 0) {
        showFb(__('The AI providers list has been updated', 'entgenai'), 'success');
        genai_prov_data[updObj.prov]['url'] = jsonResponse['provs'][updObj.prov]['url'];
        genai_prov_data[updObj.prov]['apikey'] = jsonResponse['provs'][updObj.prov]['apikey']??'';
        genai_prov_data[updObj.prov]['headers'] = jsonResponse['provs'][updObj.prov]['headers']??'';
        genai_prov_data[updObj.prov]['body'] = jsonResponse['provs'][updObj.prov]['body']??'';
        models[updObj.prov] = jsonResponse['provs'][updObj.prov]['models'];
        let selAIProv = document.getElementById('entgenai_ai_provider');
        onSelectAIProvider(selAIProv.value);
    } else {
        showFb(__('The content could not be saved, something went wrong', 'entgenai'), 'error');
    }
}
jQuery(document).ready(function ($) {
    jQuery("#progressbar").progressbar({
        value: false
    }).hide();
    fbdlg = $( "#entgenaifeedback" ).dialog({ autoOpen: false, width: 700, height: 200, classes: {
            "ui-dialog": "ui-corner-all ui-widget-shadow"
        } });
    provdlg = $( "#entgenaiprovdiv" ).dialog({ autoOpen: false, width: 600, height: 500});
});
