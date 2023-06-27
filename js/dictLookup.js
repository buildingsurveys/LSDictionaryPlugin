$(document).ready(function (){
    console.log('dictLook');
    const popoverTemplate = `
        <div class="popover" role="tooltip">
            <div class="arrow"></div>
            <h3 class="popover-title popover-header"></h3>
            <div class="popover-content popover-body"></div>
        </div>
    `;

    $(document).on('click', function (evt) {
        const selectedElement = $(evt.target);
        if ($('.popover').has(selectedElement).length === 0) {
            $('.popover').popover('hide');
        }
    });
    
    $('.info-icon, .dictLookup').on('click', function (evt) {
        evt.stopPropagation();
        evt.preventDefault();

        const selectedElement = $(evt.target);
        const dictLookupElement = selectedElement;
        const elementWithTerm = selectedElement.hasClass('dictLookup') ?
            selectedElement :
            selectedElement.parent();

        /**
        * Init
        */
        let term = replaceNbsps(elementWithTerm.text());

        /**
         * Get Term from Data?
         */
        let dataTerm = elementWithTerm.attr('data-term');
        if (typeof dataTerm != 'undefined') term = dataTerm;

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
    });

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
    
    function replaceNbsps(str) {
        let re = new RegExp(String.fromCharCode(160), "g");
        return str.replace(re, " ");
    }
});
