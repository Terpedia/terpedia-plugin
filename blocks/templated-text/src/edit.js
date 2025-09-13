import { __ } from '@wordpress/i18n';
import {
        useBlockProps,
        InspectorControls,
        RichText
} from '@wordpress/block-editor';
import {
        PanelBody,
        SelectControl,
        Button,
        Spinner,
        Notice,
        TextControl,
        TextareaControl
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { select } from '@wordpress/data';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
        const { templateId, fieldValues, content, isGenerating, selectedModel } = attributes;
        const [availableTemplates, setAvailableTemplates] = useState([]);
        const [selectedTemplate, setSelectedTemplate] = useState(null);
        const [isLoadingTemplates, setIsLoadingTemplates] = useState(true);
        const [error, setError] = useState(null);
        
        const blockProps = useBlockProps({
                className: 'templated-text-block'
        });

        // Get current post type
        const currentPostType = select('core/editor')?.getCurrentPostType() || 'post';

        // Load available templates for current post type
        useEffect(() => {
                loadTemplatesForCPT(currentPostType);
        }, [currentPostType]);

        // Load selected template details
        useEffect(() => {
                if (templateId && availableTemplates.length > 0) {
                        const template = availableTemplates.find(t => t.id === templateId);
                        setSelectedTemplate(template);
                }
        }, [templateId, availableTemplates]);

        const loadTemplatesForCPT = async (postType) => {
                setIsLoadingTemplates(true);
                setError(null);

                try {
                        const response = await fetch(window.terpediaTemplatedText.ajaxUrl, {
                                method: 'POST',
                                headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                        action: 'get_cpt_templates',
                                        post_type: postType,
                                        nonce: window.terpediaTemplatedText.nonce
                                })
                        });

                        const result = await response.json();

                        if (result.success) {
                                setAvailableTemplates(result.data.templates);
                        } else {
                                setError(result.data || 'Failed to load templates');
                                setAvailableTemplates([]);
                        }
                } catch (err) {
                        setError('Network error loading templates');
                        setAvailableTemplates([]);
                } finally {
                        setIsLoadingTemplates(false);
                }
        };

        const generateContent = async () => {
                if (!templateId || !selectedTemplate) {
                        setError('Please select a template first');
                        return;
                }

                setAttributes({ isGenerating: true });
                setError(null);

                try {
                        const response = await fetch(window.terpediaTemplatedText.ajaxUrl, {
                                method: 'POST',
                                headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams({
                                        action: 'generate_templated_content',
                                        template_id: templateId,
                                        field_values: JSON.stringify(fieldValues),
                                        post_type: currentPostType,
                                        selected_model: selectedModel,
                                        nonce: window.terpediaTemplatedText.nonce
                                })
                        });

                        const result = await response.json();

                        if (result.success) {
                                setAttributes({ 
                                        content: result.data.content,
                                        isGenerating: false 
                                });
                        } else {
                                setError(result.data || 'Failed to generate content');
                                setAttributes({ isGenerating: false });
                        }
                } catch (err) {
                        setError('Network error generating content');
                        setAttributes({ isGenerating: false });
                }
        };

        const updateFieldValue = (fieldName, value) => {
                setAttributes({
                        fieldValues: {
                                ...fieldValues,
                                [fieldName]: value
                        }
                });
        };

        const onSelectTemplate = (newTemplateId) => {
                setAttributes({ 
                        templateId: parseInt(newTemplateId),
                        fieldValues: {}, // Reset field values when template changes
                        content: '' // Clear existing content
                });
        };

        const renderTemplateFields = () => {
                if (!selectedTemplate || !selectedTemplate.fields) {
                        return null;
                }

                return (
                        <div className="template-fields">
                                <h4>{__('Template Fields', 'terpedia')}</h4>
                                {Object.entries(selectedTemplate.fields).map(([fieldName, field]) => {
                                        const currentValue = fieldValues[fieldName] || '';

                                        if (field.type === 'select' && field.options) {
                                                return (
                                                        <SelectControl
                                                                key={fieldName}
                                                                label={field.label || fieldName}
                                                                value={currentValue}
                                                                options={[
                                                                        { label: `Choose ${field.label || fieldName}...`, value: '' },
                                                                        ...field.options.map(option => ({ label: option, value: option }))
                                                                ]}
                                                                onChange={(value) => updateFieldValue(fieldName, value)}
                                                        />
                                                );
                                        } else if (field.type === 'textarea') {
                                                return (
                                                        <TextareaControl
                                                                key={fieldName}
                                                                label={field.label || fieldName}
                                                                value={currentValue}
                                                                placeholder={field.placeholder || `Enter ${field.label || fieldName}...`}
                                                                onChange={(value) => updateFieldValue(fieldName, value)}
                                                                rows={3}
                                                        />
                                                );
                                        } else {
                                                return (
                                                        <TextControl
                                                                key={fieldName}
                                                                label={field.label || fieldName}
                                                                value={currentValue}
                                                                placeholder={field.placeholder || `Enter ${field.label || fieldName}...`}
                                                                onChange={(value) => updateFieldValue(fieldName, value)}
                                                        />
                                                );
                                        }
                                })}
                        </div>
                );
        };

        const canGenerate = () => {
                if (!selectedTemplate) return false;

                // Check if all required fields are filled
                if (selectedTemplate.fields) {
                        for (const [fieldName, field] of Object.entries(selectedTemplate.fields)) {
                                if (field.required && !fieldValues[fieldName]) {
                                        return false;
                                }
                        }
                }

                return true;
        };

        return (
                <>
                        <InspectorControls>
                                <PanelBody title={__('Template Settings', 'terpedia')} initialOpen={true}>
                                        {isLoadingTemplates ? (
                                                <div className="loading-templates">
                                                        <Spinner /> {__('Loading templates...', 'terpedia')}
                                                </div>
                                        ) : (
                                                <SelectControl
                                                        label={__('Choose Template', 'terpedia')}
                                                        value={templateId}
                                                        options={[
                                                                { label: `Select a template for ${currentPostType}...`, value: 0 },
                                                                ...availableTemplates.map(template => ({
                                                                        label: `${template.name} ${template.category ? `(${template.category})` : ''}`,
                                                                        value: template.id
                                                                }))
                                                        ]}
                                                        onChange={onSelectTemplate}
                                                />
                                        )}

                                        <SelectControl
                                                label={__('AI Model', 'terpedia')}
                                                value={selectedModel}
                                                options={[
                                                        { label: 'GPT-4o (Recommended)', value: 'openai/gpt-4o' },
                                                        { label: 'GPT-4o Mini (Fast & Economical)', value: 'openai/gpt-4o-mini' },
                                                        { label: 'Claude 3.5 Sonnet (Creative)', value: 'anthropic/claude-3.5-sonnet' },
                                                        { label: 'Claude 3 Haiku (Quick)', value: 'anthropic/claude-3-haiku' },
                                                        { label: 'Llama 3.1 405B (Open Source)', value: 'meta-llama/llama-3.1-405b-instruct' },
                                                        { label: 'Llama 3.1 70B (Balanced)', value: 'meta-llama/llama-3.1-70b-instruct' },
                                                        { label: 'Llama 3.1 8B (Budget)', value: 'meta-llama/llama-3.1-8b-instruct' }
                                                ]}
                                                onChange={(model) => setAttributes({ selectedModel: model })}
                                                help={__('Choose the AI model for content generation. GPT-4o provides the best quality for scientific content.', 'terpedia')}
                                        />

                                        {selectedTemplate && (
                                                <div className="template-info" style={{ marginTop: '15px', padding: '10px', background: '#f0f8ff', borderRadius: '4px' }}>
                                                        <strong>{selectedTemplate.name}</strong>
                                                        {selectedTemplate.description && (
                                                                <p style={{ margin: '5px 0 0 0', fontSize: '13px', color: '#666' }}>
                                                                        {selectedTemplate.description}
                                                                </p>
                                                        )}
                                                </div>
                                        )}
                                </PanelBody>

                                {selectedTemplate && (
                                        <PanelBody title={__('Template Fields', 'terpedia')} initialOpen={true}>
                                                {renderTemplateFields()}
                                                
                                                <div style={{ marginTop: '15px' }}>
                                                        <Button
                                                                isPrimary
                                                                disabled={!canGenerate() || isGenerating}
                                                                onClick={generateContent}
                                                                style={{ width: '100%' }}
                                                        >
                                                                {isGenerating ? (
                                                                        <>
                                                                                <Spinner /> {__('Generating...', 'terpedia')}
                                                                        </>
                                                                ) : (
                                                                        __('Generate Content', 'terpedia')
                                                                )}
                                                        </Button>
                                                </div>
                                        </PanelBody>
                                )}
                        </InspectorControls>

                        <div {...blockProps}>
                                <div className="templated-text-header">
                                        <h4 className="block-title">
                                                {__('🤖 AI Templated Text', 'terpedia')}
                                        </h4>
                                        <div className="template-status">
                                                {selectedTemplate ? (
                                                        <span className="template-name">{selectedTemplate.name}</span>
                                                ) : (
                                                        <span className="no-template">{__('No template selected', 'terpedia')}</span>
                                                )}
                                        </div>
                                </div>

                                {error && (
                                        <Notice status="error" isDismissible={true} onRemove={() => setError(null)}>
                                                {error}
                                        </Notice>
                                )}

                                {!selectedTemplate && !isLoadingTemplates && (
                                        <div className="template-selection-prompt">
                                                <p>{__('Select a template from the sidebar to generate AI-powered content for your', 'terpedia')} <strong>{currentPostType}</strong>.</p>
                                                {availableTemplates.length === 0 && (
                                                        <p style={{ color: '#666', fontSize: '14px' }}>
                                                                {__('No templates available for this post type. Create templates in the admin area.', 'terpedia')}
                                                        </p>
                                                )}
                                        </div>
                                )}

                                {selectedTemplate && !content && (
                                        <div className="template-ready">
                                                <div className="template-preview">
                                                        <strong>{__('Template:', 'terpedia')}</strong> {selectedTemplate.name}
                                                        <p style={{ color: '#666', fontSize: '14px', margin: '5px 0' }}>
                                                                {selectedTemplate.description}
                                                        </p>
                                                        {selectedTemplate.fields && Object.keys(selectedTemplate.fields).length > 0 && (
                                                                <p style={{ fontSize: '14px' }}>
                                                                        {__('Fill in the template fields in the sidebar and click Generate Content.', 'terpedia')}
                                                                </p>
                                                        )}
                                                </div>
                                        </div>
                                )}

                                {content && (
                                        <div className="generated-content-wrapper">
                                                <div className="content-header">
                                                        <span>{__('Generated Content:', 'terpedia')}</span>
                                                        <Button
                                                                isSecondary
                                                                size="small"
                                                                onClick={() => setAttributes({ content: '' })}
                                                        >
                                                                {__('Clear', 'terpedia')}
                                                        </Button>
                                                </div>
                                                <RichText
                                                        tagName="div"
                                                        className="templated-text-content"
                                                        value={content}
                                                        onChange={(newContent) => setAttributes({ content: newContent })}
                                                        placeholder={__('Generated content will appear here...', 'terpedia')}
                                                        allowedFormats={[
                                                                'core/bold',
                                                                'core/italic',
                                                                'core/link',
                                                                'core/strikethrough',
                                                                'core/underline'
                                                        ]}
                                                        multiline="p"
                                                />
                                        </div>
                                )}
                        </div>
                </>
        );
}