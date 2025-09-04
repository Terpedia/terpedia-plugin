# Terpedia Newsletter System

## Overview

The Terpedia Newsletter System provides a comprehensive solution for creating, managing, and automating newsletter generation with configurable sections and data sources.

## Features

### 1. Newsletter Template Manager
- **Location**: Admin → Terpedia → Newsletter Templates
- **Features**:
  - Create and manage newsletter templates
  - Configure sections with custom prompts
  - Set frequency (daily, weekly, bi-weekly, monthly)
  - Define required vs optional sections
  - Set word counts and data sources for each section

### 2. Newsletter Automation
- **Location**: Admin → Terpedia → Newsletter Automation
- **Features**:
  - Automated newsletter generation based on templates
  - Scheduled generation (weekly, daily, monthly)
  - Manual newsletter generation
  - Email notifications for generated newsletters
  - Dashboard with recent newsletters and scheduled events

### 3. Data Sources
The system supports multiple data sources for newsletter sections:

- **Recent Science**: PubMed articles, research studies
- **Industry News**: Market updates, regulatory changes
- **Recent Posts**: Community content, user-generated posts
- **Agent Spotlight**: AI agent activity and insights
- **Podcast Highlights**: Recent podcast episodes
- **Community Corner**: Forum discussions and engagement
- **Quick Facts**: Terpene facts and trivia
- **Market Analysis**: Market data and trends
- **Research Spotlight**: Featured research highlights

### 4. Newsletter Sections

#### Default Template Sections:
1. **🚀 Breakthrough Highlight** (Required)
   - Recent breakthroughs in terpene research
   - 200-300 words
   - Data source: Recent PubMed articles

2. **🔬 Research Roundup** (Required)
   - 3-4 recent peer-reviewed studies
   - 300-400 words
   - Data source: Weekly PubMed articles

3. **📊 Industry Intel** (Required)
   - Industry news and market trends
   - 200-250 words
   - Data source: Industry news feeds

4. **🤖 Agent Spotlight** (Required)
   - AI agent activity and insights
   - 150-200 words
   - Data source: Agent activity

5. **📝 Recent Posts** (Optional)
   - Community content highlights
   - 150-200 words
   - Data source: Recent posts

6. **🎙️ Podcast Highlights** (Optional)
   - Recent podcast episodes
   - 150-200 words
   - Data source: Podcast episodes

7. **👥 Community Corner** (Optional)
   - Forum discussions and engagement
   - 100-150 words
   - Data source: Forum activity

8. **⚡ Quick Facts** (Optional)
   - Interesting terpene facts
   - 100-150 words
   - Data source: Terpene database

## Usage

### Creating a Newsletter Template

1. Go to **Admin → Terpedia → Newsletter Templates**
2. Click **"Create New Template"**
3. Fill in template details:
   - Name and description
   - Frequency (weekly recommended)
   - Active status
4. Add sections:
   - Section name and title
   - Section type
   - Prompt for content generation
   - Word count
   - Data source
   - Required/optional status
5. Save the template

### Generating Newsletters

#### Manual Generation:
1. Go to **Admin → Terpedia → Newsletter Automation**
2. Select template and date range
3. Choose sections to include
4. Click **"Generate Newsletter"**

#### Automated Generation:
- Weekly newsletters are automatically generated every Monday at 9 AM
- Daily newsletters run weekdays at 8 AM
- Monthly newsletters run on the 1st at 10 AM

### Frontend Display

#### Newsletter Shortcode:
```
[terpedia_newsletter limit="5" template="1"]
```

#### Newsletter Signup Shortcode:
```
[terpedia_newsletter_signup title="Subscribe to Our Newsletter" description="Get the latest updates"]
```

## Database Tables

The system creates the following database tables:

1. **wp_terpedia_newsletter_templates**: Stores newsletter templates
2. **wp_terpedia_newsletter_sections**: Stores template sections
3. **wp_terpedia_newsletter_subscribers**: Stores subscriber emails

## Custom Post Types

- **terpedia_newsletter**: Stores generated newsletter posts

## Integration Points

### With Existing Systems:
- **OpenRouter AI**: For content generation (when available)
- **Agent Activity**: For agent spotlight sections
- **Forum System**: For community corner content
- **Podcast System**: For podcast highlights
- **Research Database**: For science sections

### WordPress Integration:
- **Cron Jobs**: For scheduled generation
- **AJAX**: For admin interface interactions
- **Shortcodes**: For frontend display
- **Email System**: For notifications and welcome emails

## Configuration

### Settings:
- Default template selection
- Auto-publish option
- Email notifications
- Notification email address

### Customization:
- Add new section types
- Create custom data sources
- Modify generation prompts
- Customize email templates

## Troubleshooting

### Common Issues:

1. **Newsletters not generating automatically**:
   - Check WordPress cron is working
   - Verify template is active
   - Check for PHP errors in logs

2. **Missing data in sections**:
   - Verify data sources are configured
   - Check if source data exists
   - Review section prompts

3. **Email notifications not working**:
   - Check WordPress mail configuration
   - Verify notification email settings
   - Test with a simple email

## Future Enhancements

- Integration with external email services (Mailchimp, ConvertKit)
- Advanced AI content generation
- A/B testing for newsletter content
- Analytics and engagement tracking
- Custom email templates
- Subscriber segmentation
- Social media integration

## Support

For issues or questions about the newsletter system, check:
1. WordPress error logs
2. Plugin admin pages for error messages
3. Database table integrity
4. WordPress cron functionality
