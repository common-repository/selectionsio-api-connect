var calccount = 1;

var calcHasError = false;
var $ = jQuery.noConflict();
$(function(){
    $("#in_basket").click(function(event) {
        
        if($('#upload_mode').val() == 'none' && $('#myUpload .modal-body > div').length > 0 ) {
            $('#myUpload').modal();
            return;
        }
        var formElements = $('#CALCFORM').serializeArray();
        var formArray = {};
        $.each(formElements, function( index, value ) {
            var name = value.name;
            var val = value.value;
            formArray[name] = val;
        });

        if(productLoaded === 1) {
            $.ajax({
                url: apiseite + "/apps/api/basket/legacy/update",
                contentType: "application/json",
                dataType: 'json',
                method: 'post',
                data: JSON.stringify({productUUId: productUUId, values: formArray, count: 1, uploadMode: $('#upload_mode').val() }),
                success: function(result){
                    document.location="/basket";
                }
            });
            return false;
        }else{
            $.ajax({
                url: apiseite + "/apps/api/basket/legacy/add",
                contentType: "application/json",
                dataType: 'json',
                method: 'post',
                data: JSON.stringify({productUUId: productUUId, values: formArray, count: 1, uploadMode: $('#upload_mode').val() }),
                success: function(result){
                    document.location="/basket";
                }
            });
            return false;
        }
    });
    if(productLoaded === 1) {
        loadCalc(productUUId, true);
    }else{
        loadCalc(productUUId);
    }
})

function loadCalc(productUUId, firstLoad = false) {
    var formArray = {};

    calcHasError = false;

    if(firstLoad) {
        formArray = productValues;
    }

    var formElements = $('#CALCFORM').serializeArray();
    $.each(formElements, function( index, value ) {
        var name = value.name;
        var val = value.value;
        formArray[name] = val;
    });
    
    $.ajax({
          url: apiseite + "/apps/api/plugin/system/psc/xmlcalc/price",
          contentType: "application/json",
          dataType: 'json',
          method: 'post',
          data: JSON.stringify({product: productUUId, test: false, values : formArray}),
          success: function(result){

              buildForm(result.elements);

              if(calcHasError) {
                $('#netto').html("ERR");
                $('#mwert').html("ERR");
                $('#brutto').html("ERR");
                $("#in_basket").prop("disabled",true);
                $('#CALCFORM input, #CALCFORM select').unbind();
                $('#CALCFORM input, #CALCFORM select').change(function() {
                      loadCalc(productUUId);
                });
                // PRECALC AUSBLENDEN?
                //loadPreCalc(productUUId, false, result.preCalc);
              }else{
                $('#netto').html(new Intl.NumberFormat('de-DE', { style: 'currency', currency: productLanguage }).format(result.netto/100));
                $('#mwert').html(new Intl.NumberFormat('de-DE', { style: 'currency', currency: productLanguage }).format(result.steuer/100));
                $('#brutto').html(new Intl.NumberFormat('de-DE', { style: 'currency', currency: productLanguage }).format(result.brutto/100));
                $("#in_basket").prop("disabled",false);
                $('#CALCFORM input, #CALCFORM select').unbind();
                $('#CALCFORM input, #CALCFORM select').change(function() {
                      loadCalc(productUUId);
                });
                loadPreCalc(productUUId, false, result.preCalc);
              }
              productXml = result.xmlProduct;
        },
        error: function(err){
        }


  });

}

function loadPreCalc(productUUId, testMode, preCalc) {
    $('#preCalc').html('');
    if(preCalc.length === 0) {
        $('#preCalcContainer').hide();
    }else{
        $('#preCalcContainer').show();
    }
    $.each(preCalc, function(indexGroup, group) {
        var htmlGroup = $('<div class="col-4"><h5>' + group.name + '</h5><div class="row" id="group_' + indexGroup + '"></div></div>');
        $('#preCalc').append(htmlGroup);
        $.each(group.variants, function(indexVariant, variant) {
            var htmlVariant = $('<div class="col-6">' + variant.name + '</div><div style="text-align:right;" class="col-6" id="variant_' + indexGroup + '_' + indexVariant + '"></div>');
            $('#group_' + indexGroup).append(htmlVariant);
            
            var formElements = $('#CALCFORM').serializeArray();
            var formArray = {};
            $.each(formElements, function( index, value ) {
                var name = value.name;
                var val = value.value;
                formArray[name] = val;
            });
            $.each(variant.values, function(indexValue,value) {
                formArray[value.key] = value.value;
            });
            $.ajax({
                url: apiseite + "/apps/api/plugin/system/psc/xmlcalc/price",
                contentType: "application/json",
                dataType: 'json',
                method: 'post',
                data: JSON.stringify({product: productUUId, test: testMode, values : formArray}),
                success: function(result){       
                    $('#variant_' + indexGroup + '_' + indexVariant).html(new Intl.NumberFormat('de-DE', { style: 'currency', currency: productLanguage }).format(result.netto/100));
                }
            })
        });
    });
}

function buildForm(elements) {
    var previousId = null;

	$.each(elements, function(index, element) {
        if(element.valid === false) {
            removeElement(element);
            return;
        }
        var $label = $('<label>').addClass('form-label').text(element.name);
        $container = $('<div class="form-group" id="container_' + element.id + '"></div>');     
        if(element.htmlType == "hidden") {
                var $obj = $('<input>', {
                type: 'hidden',
              value: element.rawValue,
              id: element.id,
              name: element.id
            });
            $element = $('<div>').addClass("col-8");
            $obj.appendTo($element);

            addOrReplaceFormElement(previousId, element.id, $container, $element, null, element.valid);
            return;
        }
        if(element.htmlType == "select") {

            var $obj = $('<select>', {
              id: element.id,
              title: element.help,
              name: element.id
            });
           $.each(element.options, function(ind, opt) {
          
                if(opt.valid) {
                    $obj.append(new Option(opt.name, opt.id, opt.selected, opt.selected));
              }
           });
           $obj.addClass('form-control');
        }
        if(element.htmlType == "text") {
            var $obj = $('<input>', {
                type: 'text',
              value: element.defaultValue,
              id: element.id,
              name: element.id,
              title: element.help,
              disabled: true
            });
            $obj.addClass("form-control");
        }
        if(element.htmlType == "input") {
            var $obj = $('<input>', {
                type: 'text',
              value: element.rawValue,
              id: element.id,
              title: element.help,
              name: element.id
            });
            $obj.addClass("form-control");
         }
        
        if(element.htmlType == "checkbox") {
            var $obj = $('<checkbox>', {
                value: element.rawValue,
              id: element.id,
              title: element.help,
              name: element.id
            });
        }
        
        if(element.htmlType == "textarea") {
            var $obj = $('<textarea>', {
              value: element.rawValue,
              id: element.id,
              title: element.help,
              name: element.id
            });
        }
        
        $element = $('<div>').addClass("form-controls");
        $inputGroup = $('<div class="input-group input-group-sm">');
        $inputGroup.appendTo($element);
        $obj.appendTo($inputGroup);

        addOrReplaceFormElement(previousId, element.id, $container, $element, $label, element.valid);
        if(element.helpLink) {
            //$obj.after('<div class="input-group-append"><a class="btn btn-outline-primary btn-circle" onclick="modalopen(\'' + element.helpLink + '\');return false;" target="_blank" href="' + element.helpLink + '">?</a></div>');
        }
        if(element.validationErrors.length > 0) {
            if(calcHasError === false) {
                calcHasError = true;
            }
            $obj.addClass("is-invalid");
            $.each(element.validationErrors, function(errorIndex, error) {
                if(error.type === 'input::validation::max') {
                   $obj.parent().after($('<div class="invalid-feedback d-block">Wert muss kleiner sein als: ' + error.maxValue + '</div>')); 
                }
                if(error.type === 'input::validation::min') {
                   $obj.parent().after($('<div class="invalid-feedback d-block">Wert muss größer sein als: ' + error.minValue + '</div>')); 
                }
            });
        }else{
            $obj.removeClass("is-invalid");
        }
        previousId = element.id;
  })
  console.log(apiseite + "/article/show/uuid/" + productUUId + "?" + $("#CALCFORM").serialize());
  if(salebtn == 1) {
  $( ".salebtn" ).html('<a target="_blank" class="btn btn-primary produkt-config-btn" onclick="loadProduct(); return false;" href="' + apiseite + "/article/show/uuid/" + productUUId + "?" + $("#CALCFORM").serialize() + '">Bestellen</a>');
  }
}

function loadProduct() {
loadCalc(productUUId, firstLoad = false);
window.open(apiseite + "/article/show/uuid/" + productUUId + "?" + $("#CALCFORM").serialize(), '_blank');
}
function removeElement(element) {
    $('#container_' + element.id).remove();        
}

function addOrReplaceFormElement(previousId, id, $container, $element, $label, valid) {
    if($('#container_' + id).length > 0) {
        if(!valid) {
            $('#container_' + id).remove();
        }else{
            $('#container_' + id).html('');
            if($label !== null) {
                $label.appendTo($('#container_' + id));
            }
            $element.appendTo($('#container_' + id));
        }
    }else{
        if(previousId !== null && $('#container_' + previousId).length > 0) {
            $container.insertAfter($('#container_' +  previousId));
        }else{
		    $('#CALCFORM').append($container);
        }
        if($label !== null) {
            $label.appendTo($container);
        }
        $element.appendTo($container);
    }
}
function modalopen(href) {
    $('.help-modal').load(href,function(){
        $('#myModal').modal({show:true});});
}