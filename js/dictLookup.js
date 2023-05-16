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

        if(selectedElement.hasClass('info-icon')){
            const dictLookupElement = selectedElement;
            const parentElement = selectedElement.parent();

            /**
            * Init
            */
            let term = replaceNbsps(parentElement.text());

            /**
             * Get Term from Data?
             */
            let dataTerm = parentElement.attr('data-term');
            if (typeof dataTerm != 'undefined') term = dataTerm;

            // Compose Title
            let title = term.substring(0, 3) != 'REF' ? term : 'Reference #' +  term.substring(3);
            let lang = GetCurrPageLang();
            console.log("Setting up popover for " + term, selectedElement);
            $.ajax({
                url: _dictUrl + '&t=' + term + '&l=' + lang
            })
            .then(function(content) {
                // Set the tooltip content upon successful retrieval
    //AQUI SE ASIGNA Cnombre_const ()
                const popoverWithTerm = dictLookupElement.popover(defaultPopoverOptions(
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
    });

    function defaultPopoverOptions({content, title, placement = 'top', trigger = 'manual', ...rest}) {
        return {
            content,
            title,
            trigger,
            viewport: { selector: '.outerframe', padding: 0 },
            // viewport: $(window),
            placement: placement,
            ...rest
        }
    };
    
    function replaceNbsps(str) {
        let re = new RegExp(String.fromCharCode(160), "g");
        return str.replace(re, " ");
    }
    
});