import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import Edit from './edit';
import save from './save';
import metadata from '../block.json';

registerBlockType(metadata.name, {
	...metadata,
	title: __('Templated Text', 'terpedia'),
	description: __('Generate AI-powered content using CPT-specific templates with field inputs', 'terpedia'),
	edit: Edit,
	save,
});