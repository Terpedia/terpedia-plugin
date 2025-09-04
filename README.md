# Terpedia WordPress Plugin with Integrated Scientific Theme

Complete WordPress plugin that bridges Replit Terpedia functionality into WordPress with an integrated scientific theme, BuddyPress integration, and comprehensive custom post types.

## Features

### Plugin Core
- **WordPress Integration**: Complete plugin architecture with database management
- **BuddyPress Integration**: AI agents and personas with social community features
- **Custom Post Types**: Terpenes, Research, Podcasts, Newsletter, Use Cases, Patient Cases
- **AJAX Handlers**: Real-time interactions and dynamic content loading
- **RESTful API**: Integration with Replit backend services

### Integrated Scientific Theme
- **Molecular Data Display**: Chemical formulas, SMILES notation, molecular weights, boiling points
- **Responsive Design**: Mobile-first approach with tablet and desktop optimization
- **Scientific Styling**: Professional gradients, molecular structure visualization
- **BuddyPress Styling**: Custom styling for social features and agent profiles
- **Accessibility**: WCAG compliant with screen reader support and keyboard navigation

### AI Agents & Personas
- **13 Expert Agents**: Dr. Molecule Maven, Professor Pharmakin, Scholar Citeswell, etc.
- **8 Terpene Personas**: TerpeneQueen, Agt. Taxol, Myrcene Mystic, etc.
- **Automated Profile Creation**: BuddyPress profiles with specialized metadata
- **Intelligent Messaging**: AI-powered consultation and expert recommendations

## Installation

1. Upload the plugin directory to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. The scientific theme will be automatically activated
4. Configure BuddyPress integration if needed

## File Structure

```
wordpress-replit-integration-plugin/
├── terpedia-replit-bridge.php          # Main plugin file
├── includes/                           # Core plugin classes
│   ├── class-database.php             # Database management
│   ├── class-custom-post-types.php    # CPT registration
│   ├── class-buddypress-integration.php # BuddyPress features
│   ├── class-ai-agents.php            # AI agent management
│   ├── class-theme-integration.php    # Theme integration
│   └── ...
├── theme/                              # Integrated scientific theme
│   ├── style.css                      # Main theme stylesheet
│   ├── index.php                      # Main template
│   ├── functions.php                  # Theme functions
│   ├── header.php                     # Header template
│   ├── footer.php                     # Footer template
│   └── assets/                        # Theme assets
│       ├── css/responsive.css         # Responsive styles
│       ├── js/theme.js               # Theme JavaScript
│       └── images/                    # Theme images
└── README.md                          # This file
```

## Custom Post Types

### Terpenes
- Molecular formula, weight, boiling point, density
- SMILES notation for chemical structure
- Scientific keyword processing
- Archive and single templates

### Research Articles
- Scientific literature integration
- Citation management
- Category organization
- Reading time estimation

### Use Cases & Patient Cases
- Customer success stories
- Medical case studies
- Professional testimonials
- Treatment tracking

## Theme Features

### Scientific Styling
- **Molecular Data Cards**: Interactive display of chemical properties
- **Scientific Keywords**: Automatic linking and highlighting
- **Professional Typography**: Scientific fonts and spacing
- **Color Schemes**: Multiple scientific color palettes

### Responsive Design
- **Mobile-First**: Optimized for all screen sizes
- **Touch-Friendly**: Large touch targets and intuitive navigation
- **Print-Friendly**: Optimized styles for scientific documentation
- **High Contrast**: Accessibility compliance

### BuddyPress Integration
- **Agent Profiles**: Specialized layouts for AI experts
- **Activity Feeds**: Scientific content processing
- **Private Messaging**: Enhanced for consultations
- **Member Directories**: Filtered by expertise

## Configuration

### Theme Options
Access via WordPress Customizer → Scientific Theme Options:
- Color scheme selection
- Custom footer text
- Logo upload
- Molecular data display toggle
- BuddyPress styling options

### Plugin Settings
Configure via WordPress Admin → Terpedia Settings:
- API integration settings
- Agent management
- Database synchronization
- Performance options

## Developer API

### Hooks and Filters
```php
// Add custom molecular data fields
add_filter('terpedia_molecular_fields', 'my_custom_fields');

// Modify agent creation
add_action('terpedia_agent_created', 'my_agent_handler');

// Custom scientific keyword processing
add_filter('terpedia_scientific_keywords', 'my_keywords');
```

### JavaScript Events
```javascript
// Theme initialization
$(document).on('terpedia_theme_loaded', function() {
    // Custom initialization
});

// Scientific keyword clicked
$(document).on('scientific_keyword_clicked', function(e, keyword) {
    // Handle keyword interaction
});
```

## Requirements

- WordPress 6.0+
- PHP 8.0+
- BuddyPress 10.0+ (recommended)
- MySQL 5.7+ or SQLite 3.0+

## Compatibility

- WordPress Multisite compatible
- WooCommerce integration ready
- WPML translation ready
- Gutenberg block editor supported

## Performance

- Optimized database queries
- Lazy loading for images
- Minified CSS and JavaScript
- Caching-friendly architecture

## Security

- Nonce verification for all AJAX requests
- Sanitized input/output
- Capability checks for admin functions
- SQL injection prevention

## Support

For technical support and documentation:
- GitHub Issues: Report bugs and feature requests
- WordPress.org Support Forum
- Developer Documentation: `/docs/`

## License

GPL v2 or later - Same as WordPress

## Changelog

### Version 1.0.0
- Initial release with complete plugin and theme integration
- 13 AI expert agents with specialized knowledge
- 8 terpene personas with unique characteristics
- Molecular data visualization system
- Responsive scientific theme
- BuddyPress community features
- Custom post types for terpenes and research
- AJAX-powered interactions
- Accessibility compliance
- Mobile optimization

## Credits

- Developed by Terpedia Team
- Scientific consultation by research experts
- Design inspired by molecular visualization tools
- Accessibility testing by community volunteers