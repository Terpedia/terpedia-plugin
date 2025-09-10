# Changelog - Terpedia WordPress Plugin

All notable changes to the Terpedia WordPress plugin will be documented in this file.

## [3.9.1] - 2025-09-10

### Added
- **Plugin Version Display**: Added version information to Terpedia Dashboard System Status section
- **Automated User Maintenance**: Daily maintenance system that creates and manages all Tersonae and Expert users
- **Terproducts Custom Post Type**: Complete product management system with:
  - Label scanning functionality
  - Ingredient analysis with AI-powered detection
  - Database linking to major knowledge bases
  - Terpene profile detection and analysis
- **Database Integration**: Automatic linking to external databases:
  - ChEBI (Chemical Entities of Biological Interest)
  - PubChem (NCBI chemical compound database)
  - /cyc (Cyc Knowledge Base concepts)
  - RHEA (Biochemical reaction database)
  - UniProt (Protein and enzyme information)
  - Terpedia Encyclopedia (Internal knowledge base)
- **Secure Update System**: Added secure theme and plugin update functionality from GitHub
- **Enhanced Admin Interface**: Improved Terpedia admin dashboard with management tools

### Security
- Implemented secure admin-post handlers with nonce verification
- Added proper capability checks for all admin operations
- Removed hardcoded secrets from UI and implemented secure storage
- Added CSRF protection for all state-changing operations

### User Management
- **Expert Users**: Automated creation of medical, research, and specialized expert agents
- **Tersonae**: Automated creation of individual terpene persona users
- **BuddyPress Integration**: Full profile synchronization with comprehensive metadata
- **Role Assignment**: Proper WordPress roles and capabilities for different agent types

## [1.0.0] - 2025-09-03

### Added
- Initial release of Terpedia WordPress Plugin
- Complete terpene encyclopedia system with molecular data
- AI agent network with expert agents and Terpene personifications (Tersonas)
- ElevenLabs podcast integration with Terpene Queen voice (ID: 6RLPaN4kfXS7oqmKHRv3)
- BuddyPress integration with custom community features
- Research tools and citation management
- Multi-agent system for coordinated AI responses

### AI Agents
- **Dr. Elena Molecular** - Molecular structure analysis specialist
- **Prof. Pharmakin** - Pharmacokinetics and drug interaction expert
- **Scholar Citeswell** - Research literature analysis
- **Dr. Rebecca Chen** - Terpene formulation development
- **Dr. Paws Prescription** - Veterinary terpene applications

### Terpene Personifications (Tersonas)
- **Linalool** - Anxiety relief and calming expert
- **Humulene** - Appetite suppression and weight management
- **Limonene** - Mood enhancement and citrus therapeutics
- **Myrcene** - Sedation and muscle relaxation
- **Pinene** - Cognitive enhancement and respiratory health
- **Caryophyllene** - Pain relief and anti-inflammatory

### Podcast Features
- ElevenLabs Turbo v2.5 integration for high-quality TTS
- AI-powered script generation using GPT-4
- Custom post type for podcast episodes
- WordPress shortcodes for podcast player and generator
- Audio file management and metadata tracking

### Custom Post Types
- `terpene` - Individual terpene profiles
- `research` - Research articles and studies
- `podcast` - Podcast episodes with audio
- `newsletter` - Newsletter issues and archives
- `terpedia_podcast` - Dedicated podcast episode type

### Shortcodes
- `[terpedia_encyclopedia]` - Full encyclopedia interface
- `[terpedia_agents]` - AI agent directory
- `[terpedia_podcast_generator]` - Podcast episode generator
- `[terpedia_podcast_player id="123"]` - Audio player
- `[terpedia_newsletters]` - Newsletter archive
- `[terpedia_terports]` - Research reports

### API Integration
- OpenAI GPT-4 for AI agent responses and content generation
- ElevenLabs for podcast voice synthesis
- PubMed for scientific literature verification
- WordPress REST API for content management

### Database Features
- Custom tables for terpene data and agent interactions
- Podcast episode metadata and audio file tracking
- Research citation verification and status tracking
- User consultation history and preferences

### Requirements
- WordPress 6.0+
- PHP 8.0+
- OpenAI API key (for AI agents)
- ElevenLabs API key (for podcast generation)
- BuddyPress (optional, for community features)