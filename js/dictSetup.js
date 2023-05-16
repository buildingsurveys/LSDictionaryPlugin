String.prototype.replaceArray = function(find, replace) {
    let replaceString = this;
    let regex;
    for (let i = 0; i < find.length; i++) {
        regex = new RegExp(find[i], "g");
        replaceString = replaceString.replace(regex, replace[i]);
    }
    return replaceString;
};

function clearDictLookup()
{
    /**
     * New way of removing embedded dictLookup spans
     */
    $('.question-text .dictLookup:not([data-term]), .label-text .dictLookup:not([data-term]), .group-description-container .dictLookup:not([data-term])').contents().unwrap();
    return;
}

function recoverListDict(langCurrent)
{
    let arrayResult = [];
    $.ajax({
        url: _lookupUrl+ '&l=' + langCurrent,
        method: 'GET',
        async: false,
        dataType: 'json',
    })
    .done(function(data){
        arrayResult = data;
        console.log(arrayResult)
    })
    .fail(function(xhr){
        console.log(xhr.responseText);
    });

    return arrayResult;
}

function sortListDictByWordCount(array)
{
  function countWords(str) {
    return str.trim().split(/\s+/).length;
  }

  // set all initial frequencies for each word to zero
  var frequency = {};
  array.forEach(function(value, index) {
    frequency[value] = countWords(value);
  });

  // sort items by word count desc
  return array.sort(function(a, b) {
    return frequency[b] - frequency[a];
  });
};

function defineListDictOnSurvey(arrayDictList)
{
    // Sort array by word count as to make bigger terms to match first
    const { termScanSelector } = configs;
    arrayDictList = sortListDictByWordCount(arrayDictList);

    // Process term list
    for(let pos = 0; pos < arrayDictList.length; pos++)
    {
        let term = arrayDictList[pos].trim();
        // Search in elements and replace
        $(termScanSelector)
            .each(function(index, value){
            
            // @todo: Apply this: https://stackoverflow.com/questions/3460004/regexp-to-search-replace-only-text-not-in-html-attribute
            // Search
            let phraseText = $(this).html().trim();
            let regExpValue = new RegExp('(\\b)(' + term + ')(\\b)',"gi");
            if(!regExpValue.test(phraseText)) return;

            // Replace
            newPhrase = phraseText.replace(
                regExpValue,
                function(match, $1, $2, $3){
                    return $1 + '<span class="dictLookup">' + $2 + '</span>' + $3;
                }
            );
            $(this).html(newPhrase);

            // As the terms are getting processed from longest to smallest,
            // some nested dictLookup tags are created
            // Example terms: "formal alternative care" and "alternative care"
            // As that, we remove child ones
            $('.dictLookup > .dictLookup').contents().unwrap()

            return;
        });
    }
}

function addInformationIconToTerms(){
    let finalIcon = '<span class="fa fa-info-circle info-icon" aria-hidden="true"></span>' 
    if (typeof callbackDictLoopUpIcon != 'undefined' 
        && typeof window[callbackDictLoopUpIcon] != 'undefined') { 
        finalIcon = window[callbackDictLoopUpIcon](); 
    }

    $('.dictLookup').append(finalIcon);
}

$(document).ready(function(){
    let langCurrent = GetCurrPageLang();

    clearDictLookup()

    let arrayDictList = recoverListDict(langCurrent);
    defineListDictOnSurvey(arrayDictList);
    addInformationIconToTerms();
});