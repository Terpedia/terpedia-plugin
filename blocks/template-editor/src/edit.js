import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
	BlockControls
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	Button,
	Notice,
	TextareaControl
} from '@wordpress/components';
import {
	ToolbarGroup,
	ToolbarButton,
	DropdownMenu
} from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
	const { content, fieldPreview, showMarkup } = attributes;
	const [parsedFields, setParsedFields] = useState([]);
	const [schemaPreview, setSchemaPreview] = useState(null);
	
	const blockProps = useBlockProps({
		className: 'template-editor-block'
	});

	// Parse fields from content
	useEffect(() => {
		if (content) {
			parseTemplateFields(content);
		}
	}, [content]);

	const parseTemplateFields = (templateContent) => {
		const fieldRegex = /\{\{([^}]+)\}\}/g;
		const matches = [...templateContent.matchAll(fieldRegex)];
		const fields = [];

		matches.forEach(match => {
			const fieldDefinition = match[1];
			const parts = fieldDefinition.split(':');
			const fieldName = parts[0].trim();
			const fieldLabel = parts[1] ? parts[1].trim() : fieldName.replace(/_/g, ' ');
			const fieldPlaceholder = parts[2] ? parts[2].trim() : '';

			let fieldType = 'text';
			let options = [];

			if (fieldName.startsWith('select_') || (parts[2] && parts[2].includes(','))) {
				fieldType = 'select';
				options = parts[2] ? parts[2].split(',').map(opt => opt.trim()) : [];
			} else if (fieldName.startsWith('textarea_') || fieldName.includes('description')) {
				fieldType = 'textarea';
			}

			fields.push({
				name: fieldName,
				label: fieldLabel,
				type: fieldType,
				placeholder: fieldPlaceholder,
				options: options,
				markup: match[0]
			});
		});

		setParsedFields(fields);
	};

	const onChangeContent = (newContent) => {
		setAttributes({ content: newContent });
	};

	const insertFieldMarkup = (fieldType) => {
		let markup = '';
		switch (fieldType) {
			case 'text':
				markup = '{{field_name:Field Label}}';
				break;
			case 'textarea':
				markup = '{{textarea_description:Description}}';
				break;
			case 'select':
				markup = '{{select_category:Category:option1,option2,option3}}';
				break;
			case 'compound':
				markup = '{{compound:Compound Name}}';
				break;
			case 'condition':
				markup = '{{condition:Medical Condition}}';
				break;
		}

		// Insert at current cursor position or append
		const newContent = content + ' ' + markup + ' ';
		setAttributes({ content: newContent });
	};

	const fieldInsertOptions = [
		{
			title: __('Text Field', 'terpedia'),
			onClick: () => insertFieldMarkup('text'),
			icon: 'text'
		},
		{
			title: __('Text Area', 'terpedia'),
			onClick: () => insertFieldMarkup('textarea'),
			icon: 'text-page'
		},
		{
			title: __('Select Dropdown', 'terpedia'),
			onClick: () => insertFieldMarkup('select'),
			icon: 'list-view'
		},
		{
			title: __('Compound Field', 'terpedia'),
			onClick: () => insertFieldMarkup('compound'),
			icon: 'admin-generic'
		},
		{
			title: __('Condition Field', 'terpedia'),
			onClick: () => insertFieldMarkup('condition'),
			icon: 'admin-users'
		}
	];

	const renderFieldPreview = () => {
		if (!fieldPreview || parsedFields.length === 0) {
			return null;
		}

		return (
			<div className="template-field-preview">
				<h4>{__('Template Fields Preview', 'terpedia')}</h4>
				<div className="fields-list">
					{parsedFields.map((field, index) => (
						<div key={index} className="field-preview-item">
							<label>
								<strong>{field.label}</strong>
								{field.type === 'select' && field.options.length > 0 ? (
									<select disabled>
										{field.options.map((option, optIndex) => (
											<option key={optIndex}>{option}</option>
										))}
									</select>
								) : field.type === 'textarea' ? (
									<textarea 
										placeholder={field.placeholder || `Enter ${field.label.toLowerCase()}...`}
										disabled
										rows="3"
									/>
								) : (
									<input 
										type="text"
										placeholder={field.placeholder || `Enter ${field.label.toLowerCase()}...`}
										disabled
									/>
								)}
							</label>
						</div>
					))}
				</div>
			</div>
		);
	};

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<DropdownMenu
						icon="plus-alt2"
						label={__('Insert Field', 'terpedia')}
						controls={fieldInsertOptions}
					/>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={__('Template Settings', 'terpedia')} initialOpen={true}>
					<ToggleControl
						label={__('Show Field Preview', 'terpedia')}
						checked={fieldPreview}
						onChange={(value) => setAttributes({ fieldPreview: value })}
						help={__('Preview how template fields will appear to users', 'terpedia')}
					/>
					
					<ToggleControl
						label={__('Show Markup Guide', 'terpedia')}
						checked={showMarkup}
						onChange={(value) => setAttributes({ showMarkup: value })}
						help={__('Display template markup syntax guide', 'terpedia')}
					/>
				</PanelBody>

				{parsedFields.length > 0 && (
					<PanelBody title={__('Detected Fields', 'terpedia')} initialOpen={false}>
						<div className="detected-fields-list">
							{parsedFields.map((field, index) => (
								<div key={index} className="detected-field-item">
									<code>{field.name}</code> ({field.type})
									{field.label && <div><small>{field.label}</small></div>}
								</div>
							))}
						</div>
						<p><small>{__('These fields will be automatically generated for users', 'terpedia')}</small></p>
					</PanelBody>
				)}

				{showMarkup && (
					<PanelBody title={__('Template Markup Guide', 'terpedia')} initialOpen={false}>
						<div className="markup-guide">
							<div className="markup-example">
								<code>{'{{field_name}}'}</code>
								<small>{__('Basic text field', 'terpedia')}</small>
							</div>
							<div className="markup-example">
								<code>{'{{field:Custom Label}}'}</code>
								<small>{__('Field with custom label', 'terpedia')}</small>
							</div>
							<div className="markup-example">
								<code>{'{{field:Label:placeholder}}'}</code>
								<small>{__('Field with placeholder text', 'terpedia')}</small>
							</div>
							<div className="markup-example">
								<code>{'{{select_type:Type:opt1,opt2}}'}</code>
								<small>{__('Dropdown select field', 'terpedia')}</small>
							</div>
							<div className="markup-example">
								<code>{'{{textarea_desc:Description}}'}</code>
								<small>{__('Multi-line text area', 'terpedia')}</small>
							</div>
						</div>
					</PanelBody>
				)}
			</InspectorControls>

			<div {...blockProps}>
				<div className="template-editor-header">
					<h4 className="template-editor-title">
						{__('📝 Template Editor', 'terpedia')}
					</h4>
					<div className="template-editor-info">
						{parsedFields.length > 0 && (
							<span className="field-count">
								{parsedFields.length} {__('fields detected', 'terpedia')}
							</span>
						)}
					</div>
				</div>

				<div className="template-editor-content-area">
					<RichText
						tagName="div"
						className="template-editor-content"
						value={content}
						onChange={onChangeContent}
						placeholder={__('Write your template content here. Use {{field_name}} to create input fields...', 'terpedia')}
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

				{parsedFields.length === 0 && content.length > 10 && (
					<Notice status="info" isDismissible={false}>
						{__('💡 Add field markers like {{field_name}} to create interactive template fields', 'terpedia')}
					</Notice>
				)}

				{renderFieldPreview()}

				{parsedFields.length > 0 && (
					<div className="template-editor-footer">
						<div className="template-stats">
							<span>{__('Template ready for AI generation with', 'terpedia')} <strong>{parsedFields.length}</strong> {__('dynamic fields', 'terpedia')}</span>
						</div>
					</div>
				)}
			</div>
		</>
	);
}