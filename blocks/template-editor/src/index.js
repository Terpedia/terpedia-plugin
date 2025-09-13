import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';
import save from './save';
import metadata from '../block.json';

registerBlockType(metadata.name, {
	...metadata,
	title: __('Template Editor', 'terpedia'),
	description: __('Rich text editor with template field markup support for AI content generation', 'terpedia'),
	edit: Edit,
	save,
});