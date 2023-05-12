/** 
 * Get Language 
 */ 
 var _GetCurrPageLang = null;
 function GetCurrPageLang() 
 {
     if (_GetCurrPageLang) return _GetCurrPageLang; 
  
     let className = $('body').attr('class').match(/lang-\w+/); //get a match to match the pattern some-class-somenumber and extract that classname 
     console.log(className);
     if (className) 
     { 
         _GetCurrPageLang = className['0'].substring(5); 
         return _GetCurrPageLang; 
     } 
  
     // Return english a default 
     return 'en'; 
 } 
  
  
 function tr(txt) 
 { 
     var l = GetCurrPageLang(); 
  
     if (l == 'en') return txt; 
     if (!l) return txt; 
  
     var texts = { 
        'Save': {'es': "Guardar"}, 
        'Return': {'es': "Regresar"}, 
        'Continue': {'es': 'Continuar'}, 
        'The information has been saved.<br>Please click "Return" to exit or "Continue" to keep entering data.': {'es': 'La información ha sido guardada.<br>Por favor seleccione "Regresar" para salir o "Continuar" para continuar ingresando información.'}, 
        'Your responses were successfully saved.': {'es': 'Sus respuestas han sido correctamente guardadas.'}, 
        'Please, confirm you are ready to submit the information.': {'es': 'Confirme si desea enviar la información.'}, 
     }; 
  
     return texts[txt][l]; 
 }
 
 var _dictLookupDictionary = '{TEMPLATEURL}files/dictLookup/dictLookup.php';