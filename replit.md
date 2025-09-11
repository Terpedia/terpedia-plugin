# Terpedia WordPress Plugin

## Overview

Terpedia is a comprehensive WordPress plugin that transforms a standard WordPress site into a specialized terpene research encyclopedia and AI-powered community platform. The plugin integrates multiple systems including custom post types for scientific content, AI agent management, BuddyPress social features, and advanced content generation tools. It serves as a complete knowledge management system for terpene research, combining scientific data with AI-powered analysis and community interaction.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Plugin Architecture
- **Core Plugin Structure**: Standard WordPress plugin architecture with modular class-based design
- **File Organization**: Separated into includes/ for core classes, assets/ for CSS/JS, and theme/ for integrated scientific theme
- **Version Management**: Currently at version 3.9.4 with automated update system from GitHub
- **Database Integration**: Custom tables for specialized data with WordPress standard post types for content management

### Custom Post Types System
- **Terpenes**: Core scientific data with molecular information, SMILES notation, and chemical properties
- **Research**: Academic studies and clinical trials with citation management
- **Podcasts**: AI-generated conversations with ElevenLabs integration
- **Terports**: Research reports with AI-powered content generation using OpenRouter
- **Terproducts**: Product analysis system with photo capture and ingredient scanning
- **Recipes (Rx)**: Formulation system with percentage/mass/volume calculations and balancing tools

### AI Agent Network
- **Expert Agents**: 5 specialized AI agents (Dr. Molecular, Prof. Pharmakin, Scholar Citeswell, Dr. Formulator, Dr. Paws) with domain expertise
- **Terpene Personas (Tersonae)**: Individual terpene personifications with unique characteristics and voices
- **BuddyPress Integration**: Full social profiles with automated metadata synchronization
- **RSS Feed Monitoring**: Agents automatically monitor feeds and generate content based on keyword filtering

### Content Generation Systems
- **Terport Editor**: Enhanced content creation with OpenRouter integration for structured outputs
- **Template Management**: Configurable templates with JSON schema generation
- **Newsletter Automation**: Scheduled newsletter generation with configurable sections and data sources
- **Multimodal Generation**: Text and image generation capabilities

### Scientific Theme Integration
- **Responsive Design**: Mobile-first approach with specialized styling for scientific content
- **Molecular Visualization**: Chemical formula display with proper scientific notation
- **Accessibility Compliance**: WCAG compliant with screen reader support
- **BuddyPress Styling**: Custom social community styling integrated with scientific design

### Security Architecture
- **CSRF Protection**: Nonce verification for all state-changing operations
- **Capability Checks**: Proper WordPress role and capability validation
- **Secure Storage**: Encrypted storage for sensitive configuration data
- **Admin Post Handlers**: Secure AJAX handling with proper validation

## External Dependencies

### AI Services Integration
- **OpenRouter API**: Primary AI service for content generation with structured outputs
- **ElevenLabs**: Voice synthesis for podcast generation using Terpene Queen voice (ID: 6RLPaN4kfXS7oqmKHRv3)

### Scientific Databases
- **ChEBI**: Chemical Entities of Biological Interest database integration
- **PubChem**: NCBI chemical compound database linking
- **Cyc Knowledge Base**: Concept linking and encyclopedia integration
- **RHEA**: Biochemical reaction database
- **UniProt**: Protein and enzyme information database

### WordPress Ecosystem
- **BuddyPress**: Social networking functionality and user profiles
- **WordPress Core**: Standard post types, user management, and REST API
- **WordPress Multisite**: Compatible with network installations

### Development Tools
- **GitHub Integration**: Automated updates and version control
- **jQuery**: Frontend JavaScript functionality
- **CSS Grid/Flexbox**: Modern responsive layouts
- **WordPress AJAX**: Real-time content updates and form handling

### Media and File Processing
- **WordPress Media Library**: Photo storage and management for Terproducts
- **Image Processing**: Built-in WordPress image handling for product photos
- **File Upload Security**: WordPress-standard file validation and sanitization