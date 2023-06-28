$(document).ready(function (){
    console.log('dictLook');
    const popoverTemplate = `
        <div class="popover" role="tooltip">
            <div class="arrow"></div>
            <h3 class="popover-title popover-header"></h3>
            <div class="popover-content popover-body"></div>
        </div>
    `;

    $(document).on('click', documentClickHandler);
    function documentClickHandler (evt) 
    {
        const selectedElement = $(evt.target);
        // If clicking outside a popover, hide popovers.
        if ($('.popover').has(selectedElement).length === 0) {
            hideAllPopovers();
        }
    }
    
    function hideAllPopovers()
    {
        $('.popover').popover('hide');
    }
    
    $(document).on('click', '.info-icon, .dictLookup', termClickHandler);
    function termClickHandler (evt) {
        evt.stopPropagation();
        evt.preventDefault();

        const selectedElement = $(evt.target);
        const dictLookupElement = selectedElement;

        // Get the term
        term = getTermFromElement(selectedElement)

        // Compose Title
        let title = term.substring(0, 3) != 'REF' ? term : 'Reference #' +  term.substring(3);
        let lang = GetCurrPageLang();
        console.log("Setting up popover for " + term, selectedElement);
        $.ajax({
            url: _dictUrl + '&t=' + title + '&l=' + lang
        })
        .then(function(content) {
            // Set the tooltip content upon successful retrieval
            dictLookupElement.popover(defaultPopoverOptions(
                {
                    title,
                    html: true,
                    placement: 'auto',
                    content,
                    template: popoverTemplate,
                })
            ).popover('show');

        });
    }

    function defaultPopoverOptions({content, title, placement = 'top', trigger = 'manual', ...rest}) {
        return {
            content,
            title,
            trigger,
            container: 'body',
            placement: placement,
            ...rest
        }
    };
    
});

function getTermFromElement(selectedElement)
{
    // Get source term element
    let elementWithTerm = selectedElement.hasClass('dictLookup') ?
        selectedElement :
        selectedElement.parents('.dictLookup');

    // If no elementWithTerm found, go back to the selectedElement
    if (elementWithTerm.length == 0) elementWithTerm = selectedElement;
        
    // Get Term from Data?
    let dataTerm = elementWithTerm.attr('data-term');
    if (typeof dataTerm != 'undefined') {
        return dataTerm;
    }
    
    // Get term from text
    let term = replaceNbsps(elementWithTerm.text());

    return term;
}

function replaceNbsps(str)
{
    let re = new RegExp(String.fromCharCode(160), "g");
    return str.replace(re, " ");
}
