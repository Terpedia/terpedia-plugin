import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save({ attributes }) {
	const { content } = attributes;
	
	const blockProps = useBlockProps.save({
		className: 'templated-text-saved'
	});

	return (
		<div {...blockProps}>
			<div className="templated-text-content">
				<RichText.Content
					tagName="div"
					value={content}
				/>
			</div>
		</div>
	);
}