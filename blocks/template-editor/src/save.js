import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save({ attributes }) {
	const { content } = attributes;
	
	const blockProps = useBlockProps.save({
		className: 'template-editor-saved'
	});

	return (
		<div {...blockProps}>
			<div className="template-editor-content">
				<RichText.Content
					tagName="div"
					value={content}
				/>
			</div>
		</div>
	);
}