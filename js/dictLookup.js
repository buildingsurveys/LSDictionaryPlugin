$(document).on('dictLook.termsSet',function (){
    console.log('dictLook: Binding terms');
    
    const popoverTemplate = `
        <div class="popover" role="tooltip">
            <div class="arrow"></div>
            <h3 class="popover-title popover-header"></h3>
            <div class="popover-content popover-body"></div>
        </div>
    `;
    
    function hideAllPopovers() {
        $('.popover').popover('hide');
    }

    // If clicking outside a popover, hide popovers.
    function popoverHideHandler (evt) {
        const selectedElement = $(evt.target);
        if ($('.popover').has(selectedElement).length === 0) {
            hideAllPopovers();
        }
    }

    $(document).on('click tap touchstart', popoverHideHandler);

    let termClickHandler = function(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        hideAllPopovers();

        const selectedElement = $(evt.target);
        const dictLookupElement = selectedElement;

        // Get the term
        term = getTermFromElement(selectedElement);

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
            
            // Trigger termShown Event
            dictLookupElement.trigger('dictLook.termShown');
    
        });
    }

    $(document).on('click', '.info-icon, .dictLookup', termClickHandler);

    function defaultPopoverOptions({content, title, placement = 'top', trigger = 'manual', ...rest}) {
        return {
            content,
            title,
            trigger,
            animation: false,
            container: 'body',
            placement: placement,
            ...rest
        }
    };
    
    // Trigger initialized Event
    $(document).trigger('dictLook.initialized');
});

function getTermFromElement(selectedElement)
{
    // Get source term element
    let elementWithTerm = selectedElement.hasClass('dictLookup') ?
        selectedElement :
        selectedElement.parent();

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
