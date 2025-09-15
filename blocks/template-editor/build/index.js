/* Minimal functional build for template-editor block */
(function() {
    var wp = window.wp;
    var createElement = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var RichText = wp.blockEditor.RichText;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    
    registerBlockType('terpedia/template-editor', {
        title: __('Template Editor', 'terpedia'),
        icon: 'editor-code',
        category: 'text',
        attributes: {
            content: { type: 'string', default: '' },
            fields: { type: 'object', default: {} }
        },
        edit: function(props) {
            var blockProps = useBlockProps();
            
            return createElement('div', blockProps,
                createElement(InspectorControls, null,
                    createElement(PanelBody, { title: __('Template Settings', 'terpedia') },
                        createElement('p', null, __('Use {{field_name}} syntax for dynamic fields', 'terpedia'))
                    )
                ),
                createElement('div', { className: 'template-editor-wrapper' },
                    createElement('h4', null, __('📝 Template Editor', 'terpedia')),
                    createElement(RichText, {
                        tagName: 'div',
                        className: 'template-content',
                        value: props.attributes.content,
                        onChange: function(content) {
                            props.setAttributes({ content: content });
                        },
                        placeholder: __('Write your template with {{field_name}} placeholders...', 'terpedia')
                    })
                )
            );
        },
        save: function(props) {
            return createElement(RichText.Content, {
                tagName: 'div',
                className: 'template-content',
                value: props.attributes.content
            });
        }
    });
})();