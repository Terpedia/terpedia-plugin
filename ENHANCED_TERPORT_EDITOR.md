# Enhanced Terport Editor

The Enhanced Terport Editor provides advanced AI-powered content generation capabilities for Terports, integrating with OpenRouter's structured outputs and multimodal capabilities.

## Features

### 🤖 AI-Powered Content Generation
- **Auto-Detect Type**: Automatically analyzes the title to determine the most appropriate Terport type
- **Smart Description Generation**: Creates compelling descriptions based on the title and context
- **Structured Content Generation**: Uses OpenRouter's structured outputs to generate consistent, well-formatted content
- **Multimodal Image Generation**: Generates feature images using AI based on content context

### 📝 Template System
- **Pre-built Templates**: Six default templates for different Terport types:
  - Research Analysis
  - Compound Profile
  - Clinical Study
  - Market Analysis
  - Regulatory Update
  - Industry News
- **Custom Templates**: Create and manage custom templates with structured output mapping
- **Template Editor**: Visual template editor with section management and schema validation

### 🎯 Terport Types
1. **Research Analysis**: Comprehensive research reports with methodology, findings, and implications
2. **Compound Profile**: Detailed terpene compound profiles with chemical and biological data
3. **Clinical Study**: Clinical trial reports with statistical analysis and safety data
4. **Market Analysis**: Industry market analysis with trends and opportunities
5. **Regulatory Update**: Regulatory compliance updates with implementation guidance
6. **Industry News**: Industry news and developments with expert commentary

## Usage

### Creating a New Terport

1. **Navigate to Terports**: Go to `Terports > Add New` in the WordPress admin
2. **Enter Title**: Add a descriptive title for your Terport
3. **Auto-Detect Type**: Click "🎯 Auto-Detect Type" to automatically determine the type and generate a description
4. **Select Template**: Choose from available templates or create a custom one
5. **Add Generation Prompt**: Describe what you want to generate
6. **Generate Content**: Click "🚀 Generate Content" to create structured content
7. **Generate Image**: Click "🖼️ Generate Feature Image" to create a relevant image

### Template Management

1. **Access Templates**: Go to `Terports > Templates` in the admin menu
2. **Create Template**: Click "Add New Template" to create a custom template
3. **Define Sections**: Use `{{section_name}}` syntax to define content sections
4. **Generate Schema**: The system automatically generates JSON schema for structured outputs
5. **Validate Template**: Ensure all sections are properly defined

## Technical Implementation

### OpenRouter Integration

The system integrates with OpenRouter's structured outputs feature:

```json
{
  "response_format": {
    "type": "json_schema",
    "json_schema": {
      "name": "terport_content",
      "strict": true,
      "schema": {
        "type": "object",
        "properties": {
          "section_name": {
            "type": "string",
            "description": "Content for the section"
          }
        },
        "required": ["section_name"],
        "additionalProperties": false
      }
    }
  }
}
```

### Template Structure

Templates use a simple placeholder system:

```html
<h1>{{title}}</h1>
<h2>Executive Summary</h2>
{{executive_summary}}

<h2>Key Findings</h2>
{{key_findings}}
```

### API Endpoints

- `terpedia_generate_terport_type`: Analyzes title and generates type/description
- `terpedia_generate_terport_content`: Generates structured content using templates
- `terpedia_generate_terport_image`: Generates feature images
- `terpedia_get_terport_templates`: Retrieves available templates
- `terpedia_save_terport_template`: Saves custom templates
- `terpedia_delete_terport_template`: Deletes templates

## Configuration

### OpenRouter API Key

Configure your OpenRouter API key in the plugin settings:

1. Go to `Terpedia > Settings`
2. Enter your OpenRouter API key
3. Save settings

### Model Selection

The system uses `openai/gpt-4o` by default, but can be configured to use other models supported by OpenRouter.

## File Structure

```
wp-content/plugins/terpedia-plugin/
├── includes/
│   ├── enhanced-terport-editor.php          # Main editor functionality
│   ├── terport-openrouter-integration.php   # OpenRouter API integration
│   ├── terport-template-system.php          # Template management system
│   └── default-terport-templates.php        # Default templates
├── assets/
│   ├── css/
│   │   └── terport-editor.css              # Editor styles
│   └── js/
│       └── terport-editor.js               # Editor JavaScript
└── terpedia.php                            # Main plugin file
```

## Default Templates

### Research Analysis Template
- Executive Summary
- Introduction
- Methodology
- Key Findings
- Results and Analysis
- Clinical Implications
- Future Research Directions
- Conclusion
- References

### Compound Profile Template
- Chemical Structure
- Biological Activity
- Therapeutic Effects
- Mechanism of Action
- Safety Profile
- Clinical Applications
- Drug Interactions
- Research Evidence
- Future Research

### Clinical Study Template
- Study Overview
- Objectives
- Methodology
- Results
- Statistical Analysis
- Adverse Events
- Clinical Significance
- Limitations
- Conclusion
- References

### Market Analysis Template
- Executive Summary
- Market Overview
- Key Trends
- Market Size and Growth
- Competitive Landscape
- Key Players
- Market Opportunities
- Challenges and Risks
- Future Outlook
- Investment Implications

### Regulatory Update Template
- Update Summary
- Key Changes
- Regulatory Details
- Compliance Requirements
- Impact on Industry
- Implementation Timeline
- Next Steps
- Resources

### Industry News Template
- News Summary
- Key Developments
- Industry Impact
- Expert Commentary
- Market Reaction
- Related Developments
- Future Implications
- Additional Resources

## Customization

### Adding New Terport Types

1. Update the type options in `enhanced-terport-editor.php`
2. Add corresponding system prompts in `terport-openrouter-integration.php`
3. Create default templates in `default-terport-templates.php`

### Custom Templates

Templates are stored as custom post types (`terpedia_terport_template`) with:
- Template content with `{{section}}` placeholders
- Template type metadata
- JSON schema for structured outputs

### Styling

Customize the editor appearance by modifying `assets/css/terport-editor.css`.

## Troubleshooting

### Common Issues

1. **API Key Not Working**: Ensure your OpenRouter API key is valid and has sufficient credits
2. **Templates Not Loading**: Check that templates are published and have the correct post type
3. **Content Not Generating**: Verify the generation prompt is clear and specific
4. **Images Not Generating**: Ensure the multimodal model is available and properly configured

### Debug Mode

Enable debug mode by adding to `wp-config.php`:
```php
define('TERPEDIA_DEBUG', true);
```

## Future Enhancements

- Integration with additional image generation services
- Advanced template inheritance and composition
- Real-time collaboration features
- Export to various formats (PDF, Word, etc.)
- Integration with external research databases
- Automated fact-checking and citation management

## Support

For support and feature requests, please contact the Terpedia development team.
