( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;
    var RichText = blockEditor.RichText;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;

    blocks.registerBlockType( 'cad/alert-box', {
        edit: function( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            // This handles the input of text
            function onChangeContent( newContent ) {
                setAttributes( { message: newContent } );
            }

            // This handles the sidebar dropdown
            function onChangeType( newType ) {
                setAttributes( { alertType: newType } );
            }

            var blockProps = useBlockProps( {
                className: 'cad-alert-box-editor ' + attributes.alertType
            } );

            return el(
                'div',
                blockProps,
                [
                    // Sidebar Controls
                    el( InspectorControls, {},
                        el( PanelBody, { title: 'Alert Settings', initialOpen: true },
                            el( SelectControl, {
                                label: 'Alert Type',
                                value: attributes.alertType,
                                options: [
                                    { label: 'Blue Info', value: 'info' },
                                    { label: 'Red Warning', value: 'warning' },
                                    { label: 'Green Success', value: 'success' }
                                ],
                                onChange: onChangeType
                            } )
                        )
                    ),
                    // The Visual Editor Box
                    el( RichText, {
                        tagName: 'p',
                        className: 'cad-alert-message',
                        value: attributes.message,
                        onChange: onChangeContent,
                        placeholder: 'Write your alert message here...'
                    } )
                ]
            );
        },
        save: function() {
            // We return null because we are using render.php (Server Side Rendering)
            return null; 
        },
    } );
}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components ) );