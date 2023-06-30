
function specIcon(imageName) { 
    return `<img src="${assetsUrl}/${imageName}" alt="info-icon" class="info-icon" width="15" height="15" />`; 
}

const configs = {
    termScanSelector: 'div.question-text, .answer-container, div.question-valid-container, div.question-help-container',
    callbackDictLookupIcon: 'specIcon',
    iconImage: 'info-icon.png',
};
