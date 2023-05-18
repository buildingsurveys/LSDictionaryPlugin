/** 
 * Get Language 
 */ 
var _GetCurrPageLang = null;

function GetCurrPageLang() 
{
    if(_GetCurrPageLang) return _GetCurrPageLang;

    let className = $('body').attr('class').match(/lang-\w+/); //get a match to match the pattern some-class-somenumber and extract that classname

    if (className) {
        _GetCurrPageLang = className['0'].substring(5);
        return _GetCurrPageLang;
    }

    // Return english a default
    return 'en';
}
