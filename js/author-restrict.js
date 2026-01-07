const { addFilter } = wp.hooks;
const { __ } = wp.i18n;

addFilter(
    'i18n.gettext',
    'custom/change-save-draft-text',
    function ( translation, text, domain ) {

        if ( text === 'Save draft' ) {
            return 'Ready for Review';
        }

        return translation;
    }
);
