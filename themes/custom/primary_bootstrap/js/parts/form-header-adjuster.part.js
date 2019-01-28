(function ($) {
    BF.init(function () {

        // on the two given pages, cross-links the inactive headers to the page where they actually work
        // one page has two sortable columns, the other page has a view with a single sortable column
        // one view (with two sortable columns) is a straight-forward table, the other a grouped table
        var sUrl = "";
        var $aHeaderElements = null;
        var sOldColumnHeader = "";

        if (document.location.pathname == "/insurance-benefits/benefits-forms") {
            sUrl = "/insurance-benefits/benefits-forms-plan?order=field_benefit_plan_ref&sort=asc";
            $aHeaderElements = $('th.views-field.views-field-field-benefit-plan-ref');
            sOldColumnHeader = $aHeaderElements.html();
            $aHeaderElements.html('<a href="'+sUrl+'">'+sOldColumnHeader+'</a>');
        }
        else if (document.location.pathname == "/insurance-benefits/benefits-forms-plan") {

            // first, Form # (document id)
            sUrl = "/insurance-benefits/benefits-forms?order=field_document_id&sort=asc";
            $aHeaderElements = $('th.views-field.views-field-field-document-id');
            sOldColumnHeader = $aHeaderElements.html();
            $aHeaderElements.html('<a href="'+sUrl+'">'+sOldColumnHeader+'</a>');

            // then, Form Name (title+document link)
            sUrl = "/insurance-benefits/benefits-forms?order=title&sort=asc";
            $aHeaderElements = $('th.views-field.views-field-title');
            sOldColumnHeader = $aHeaderElements.html();
            $aHeaderElements.html('<a href="'+sUrl+'">'+sOldColumnHeader+'</a>');
        }

    });
})(jQuery);