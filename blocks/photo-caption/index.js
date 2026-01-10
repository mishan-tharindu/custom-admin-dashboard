( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;
    var RichText = blockEditor.RichText;

    blocks.registerBlockType( 'cad/photo-caption', {
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            function onChangeCaption( newContent ) {
                setAttributes( { caption: newContent } );
            }

            // useBlockProps automatically injects the styles/classes 
            // selected in the native sidebar settings.
            var blockProps = useBlockProps( {
                className: 'cad-photo-caption-block'
            } );

            return el(
                'div',
                blockProps,
                [
                    // The Visual Input Area
                    el( RichText, {
                        tagName: 'p',
                        value: attributes.caption,
                        onChange: onChangeCaption,
                        placeholder: 'Write caption...',
                        style: { margin: 0 },
                        allowedFormats: [ 'core/link', 'core/bold', 'core/italic' ] 
                    } )
                ]
            );
        },
        save: function() {
            return null; 
        },
    } );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components ) );