/* Minimal functional build for templated-text block */
(function() {
    var wp = window.wp;
    var createElement = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var Button = wp.components.Button;
    var TextControl = wp.components.TextControl;
    
    registerBlockType('terpedia/templated-text', {
        title: __('Templated Text', 'terpedia'),
        icon: 'editor-quote',
        category: 'text',
        attributes: {
            templateId: { type: 'number', default: 0 },
            selectedModel: { type: 'string', default: 'openai/gpt-5' },
            content: { type: 'string', default: '' }
        },
        edit: function(props) {
            var blockProps = useBlockProps();
            
            return createElement('div', blockProps,
                createElement(InspectorControls, null,
                    createElement(PanelBody, { title: __('Template Settings', 'terpedia') },
                        createElement(SelectControl, {
                            label: __('AI Model', 'terpedia'),
                            value: props.attributes.selectedModel,
                            options: [
                                { label: 'GPT-5 (Default)', value: 'openai/gpt-5' },
                                { label: 'GPT-4o', value: 'openai/gpt-4o' },
                                { label: 'Claude 3.5 Sonnet', value: 'anthropic/claude-3.5-sonnet' }
                            ],
                            onChange: function(value) {
                                props.setAttributes({ selectedModel: value });
                            }
                        })
                    )
                ),
                createElement('div', { className: 'templated-text-placeholder' },
                    createElement('h4', null, __('🤖 AI Templated Text', 'terpedia')),
                    createElement('p', null, __('Select a template and generate AI content', 'terpedia'))
                )
            );
        },
        save: function(props) {
            return createElement('div', { className: 'templated-text-content' },
                props.attributes.content
            );
        }
    });
})();