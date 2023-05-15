$(document).ready(function (){
console.log('dictLook');

// Apply popover to all elements of class "dictLookup"
$('.dictLookup').each(function() {
    /**
     * Init
     */
    let $this = $(this);
    let term = replaceNbsps($this.text());

    /**
     * Get Term from Data?
     */
    let dataTerm = $this.attr('data-term');
    if (typeof dataTerm != 'undefined') term = dataTerm;

    const popoverTemplate = `
        <div class="popover" role="tooltip">
            <div class="arrow"></div>
            <h3 class="popover-title popover-header"></h3>
            <div class="popover-content"></div>
        </div>
    `;

    // Compose Title
    let title = term.substring(0, 3) != 'REF' ? term : 'Reference #' +  term.substring(3);
    let lang = GetCurrPageLang();
    console.log("Setting up popover for " + term, $this);
    const firstPopover = $(this).children().popover(defaultPopoverOptions(
        {
            content: 'Loading...',
            title,
            template: popoverTemplate,
        })
    );
    const dictLookupElement = $(this).children();
    $.ajax({
        url: _dictUrl + '&t=' + term + '&l=' + lang
    })
    .then(function(content) {
        // Set the tooltip content upon successful retrieval
        firstPopover.popover('destroy');
        dictLookupElement.popover(defaultPopoverOptions(
            {
                title,
                html: true,
                placement: 'auto',
                content,
                template: popoverTemplate,
            })
        );
    });
});

function defaultPopoverOptions({content, title, placement = 'top', trigger = 'click', ...rest}) {
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