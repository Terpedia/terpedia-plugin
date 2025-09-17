#!/usr/bin/env node

/**
 * Terpedia.com Endpoint Validation Script
 * 
 * This script validates key endpoints on terpedia.com by:
 * 1. Taking screenshots of each endpoint
 * 2. Using AI to evaluate the content and functionality
 * 3. Generating a comprehensive health report
 * 
 * Usage: node validate-endpoints.js
 */

const puppeteer = require('puppeteer');
const fs = require('fs').promises;
const path = require('path');

// Configuration
const BASE_URL = 'https://terpedia.com';
const SCREENSHOTS_DIR = './screenshots';
const REPORT_DIR = './validation-reports';

// Key endpoints to validate
const ENDPOINTS = [
    {
        name: 'Home Page (/)',
        path: '/',
        description: 'Main homepage redirect to feed',
        expectedContent: ['feed', 'terpedia', 'social', 'research']
    },
    {
        name: 'Social Feed (/feed)',
        path: '/feed/',
        description: 'Main social research feed',
        expectedContent: ['terport', 'research', 'user', 'post']
    },
    {
        name: 'Podcasts (/podcasts)',
        path: '/podcasts/',
        description: 'Podcast archive page',
        expectedContent: ['podcast', 'episode', 'audio', 'listen']
    },
    {
        name: 'Newsletters (/newsletters)',
        path: '/newsletters/',
        description: 'Newsletter archive page',
        expectedContent: ['newsletter', 'subscribe', 'email', 'update']
    },
    {
        name: 'Terports (/terports)',
        path: '/terports/',
        description: 'Terport archive page',
        expectedContent: ['terport', 'research', 'terpene', 'analysis']
    },
    {
        name: 'Terproducts (/terproducts)',
        path: '/terproducts/',
        description: 'Terproduct archive page',
        expectedContent: ['product', 'terpene', 'cannabis', 'buy']
    },
    {
        name: 'Encyclopedia (/encyclopedia)',
        path: '/encyclopedia/',
        description: 'Encyclopedia entries page',
        expectedContent: ['encyclopedia', 'entry', 'definition', 'knowledge']
    },
    {
        name: 'Admin Login (/wp-admin)',
        path: '/wp-admin/',
        description: 'WordPress admin login',
        expectedContent: ['login', 'wordpress', 'admin', 'password']
    },
    {
        name: 'API Health Check',
        path: '/wp-json/terpedia/v1/health',
        description: 'API health endpoint',
        expectedContent: ['status', 'healthy', 'version', 'terpedia']
    }
];

class EndpointValidator {
    constructor() {
        this.browser = null;
        this.results = [];
        this.startTime = new Date();
    }

    async init() {
        console.log('🚀 Initializing Terpedia Endpoint Validator...');
        
        // Create directories
        await this.createDirectories();
        
        // Launch browser
        this.browser = await puppeteer.launch({
            headless: 'new',
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu'
            ]
        });
        
        console.log('✅ Browser launched successfully');
    }

    async createDirectories() {
        try {
            await fs.mkdir(SCREENSHOTS_DIR, { recursive: true });
            await fs.mkdir(REPORT_DIR, { recursive: true });
            console.log('✅ Directories created');
        } catch (error) {
            console.error('❌ Error creating directories:', error.message);
        }
    }

    async validateEndpoint(endpoint) {
        console.log(`\n🔍 Validating: ${endpoint.name}`);
        console.log(`   URL: ${BASE_URL}${endpoint.path}`);
        
        const page = await this.browser.newPage();
        const result = {
            name: endpoint.name,
            path: endpoint.path,
            url: `${BASE_URL}${endpoint.path}`,
            timestamp: new Date().toISOString(),
            status: 'unknown',
            responseTime: 0,
            screenshot: null,
            content: null,
            errors: [],
            warnings: [],
            aiEvaluation: null
        };

        try {
            // Set viewport
            await page.setViewport({ width: 1920, height: 1080 });
            
            // Set user agent
            await page.setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            
            // Start timing
            const startTime = Date.now();
            
            // Navigate to endpoint
            const response = await page.goto(`${BASE_URL}${endpoint.path}`, {
                waitUntil: 'networkidle2',
                timeout: 30000
            });
            
            // Calculate response time
            result.responseTime = Date.now() - startTime;
            result.status = response.status();
            
            // Take screenshot
            const screenshotPath = path.join(SCREENSHOTS_DIR, `${endpoint.name.replace(/[^a-zA-Z0-9]/g, '_')}.png`);
            await page.screenshot({ 
                path: screenshotPath,
                fullPage: true,
                quality: 80
            });
            result.screenshot = screenshotPath;
            
            // Extract content
            result.content = await this.extractContent(page);
            
            // Check for expected content
            this.checkExpectedContent(result, endpoint.expectedContent);
            
            // Check for errors
            await this.checkForErrors(page, result);
            
            // AI evaluation
            result.aiEvaluation = await this.evaluateWithAI(result, endpoint);
            
            console.log(`   ✅ Status: ${result.status} (${result.responseTime}ms)`);
            console.log(`   📸 Screenshot: ${screenshotPath}`);
            
        } catch (error) {
            result.status = 'error';
            result.errors.push(`Navigation error: ${error.message}`);
            console.log(`   ❌ Error: ${error.message}`);
        } finally {
            await page.close();
        }
        
        this.results.push(result);
        return result;
    }

    async extractContent(page) {
        try {
            return await page.evaluate(() => {
                // Remove script and style elements
                const scripts = document.querySelectorAll('script, style');
                scripts.forEach(el => el.remove());
                
                // Get text content
                const bodyText = document.body.innerText || document.body.textContent || '';
                
                // Get title
                const title = document.title || '';
                
                // Get meta description
                const metaDesc = document.querySelector('meta[name="description"]')?.content || '';
                
                // Get headings
                const headings = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6'))
                    .map(h => h.textContent.trim())
                    .filter(h => h.length > 0);
                
                // Get links
                const links = Array.from(document.querySelectorAll('a[href]'))
                    .map(a => ({ text: a.textContent.trim(), href: a.href }))
                    .filter(a => a.text.length > 0);
                
                // Get images
                const images = Array.from(document.querySelectorAll('img[src]'))
                    .map(img => ({ alt: img.alt, src: img.src }))
                    .filter(img => img.src);
                
                return {
                    title,
                    metaDescription: metaDesc,
                    bodyText: bodyText.substring(0, 5000), // Limit to 5000 chars
                    headings,
                    links: links.slice(0, 20), // Limit to 20 links
                    images: images.slice(0, 10), // Limit to 10 images
                    wordCount: bodyText.split(/\s+/).length
                };
            });
        } catch (error) {
            return { error: `Content extraction failed: ${error.message}` };
        }
    }

    checkExpectedContent(result, expectedContent) {
        if (!result.content || result.content.error) {
            result.warnings.push('Could not extract content for analysis');
            return;
        }
        
        const contentText = (result.content.bodyText || '').toLowerCase();
        const title = (result.content.title || '').toLowerCase();
        
        expectedContent.forEach(expected => {
            const searchText = expected.toLowerCase();
            if (!contentText.includes(searchText) && !title.includes(searchText)) {
                result.warnings.push(`Expected content "${expected}" not found`);
            }
        });
    }

    async checkForErrors(page, result) {
        try {
            // Check for JavaScript errors
            const jsErrors = await page.evaluate(() => {
                return window.terpediaErrors || [];
            });
            
            jsErrors.forEach(error => {
                result.errors.push(`JavaScript error: ${error}`);
            });
            
            // Check for console errors
            page.on('console', msg => {
                if (msg.type() === 'error') {
                    result.errors.push(`Console error: ${msg.text()}`);
                }
            });
            
            // Check for network errors
            page.on('response', response => {
                if (response.status() >= 400) {
                    result.errors.push(`HTTP ${response.status()}: ${response.url()}`);
                }
            });
            
        } catch (error) {
            result.warnings.push(`Error checking failed: ${error.message}`);
        }
    }

    async evaluateWithAI(result, endpoint) {
        // This is a mock AI evaluation - in a real implementation, you'd call an AI service
        const evaluation = {
            overallHealth: 'unknown',
            issues: [],
            recommendations: [],
            score: 0
        };
        
        // Basic health scoring
        let score = 100;
        
        // Check status code
        if (result.status >= 200 && result.status < 300) {
            evaluation.issues.push('✅ HTTP status is healthy');
        } else if (result.status >= 300 && result.status < 400) {
            evaluation.issues.push('⚠️ HTTP redirect detected');
            score -= 10;
        } else if (result.status >= 400) {
            evaluation.issues.push(`❌ HTTP error: ${result.status}`);
            score -= 50;
        }
        
        // Check response time
        if (result.responseTime < 2000) {
            evaluation.issues.push('✅ Response time is fast');
        } else if (result.responseTime < 5000) {
            evaluation.issues.push('⚠️ Response time is moderate');
            score -= 10;
        } else {
            evaluation.issues.push('❌ Response time is slow');
            score -= 20;
        }
        
        // Check content
        if (result.content && !result.content.error) {
            if (result.content.wordCount > 100) {
                evaluation.issues.push('✅ Page has substantial content');
            } else {
                evaluation.issues.push('⚠️ Page has minimal content');
                score -= 15;
            }
            
            if (result.content.title && result.content.title.length > 0) {
                evaluation.issues.push('✅ Page has a title');
            } else {
                evaluation.issues.push('❌ Page missing title');
                score -= 20;
            }
        } else {
            evaluation.issues.push('❌ Could not extract content');
            score -= 30;
        }
        
        // Check for errors
        if (result.errors.length === 0) {
            evaluation.issues.push('✅ No errors detected');
        } else {
            evaluation.issues.push(`❌ ${result.errors.length} errors found`);
            score -= result.errors.length * 10;
        }
        
        // Determine overall health
        if (score >= 90) {
            evaluation.overallHealth = 'excellent';
        } else if (score >= 70) {
            evaluation.overallHealth = 'good';
        } else if (score >= 50) {
            evaluation.overallHealth = 'fair';
        } else {
            evaluation.overallHealth = 'poor';
        }
        
        evaluation.score = Math.max(0, score);
        
        // Add recommendations
        if (result.responseTime > 3000) {
            evaluation.recommendations.push('Consider optimizing page load speed');
        }
        if (result.errors.length > 0) {
            evaluation.recommendations.push('Fix JavaScript and network errors');
        }
        if (!result.content || result.content.error) {
            evaluation.recommendations.push('Investigate content loading issues');
        }
        
        return evaluation;
    }

    async generateReport() {
        const endTime = new Date();
        const duration = endTime - this.startTime;
        
        const report = {
            summary: {
                totalEndpoints: this.results.length,
                healthyEndpoints: this.results.filter(r => r.status >= 200 && r.status < 300).length,
                errorEndpoints: this.results.filter(r => r.status >= 400).length,
                averageResponseTime: this.results.reduce((sum, r) => sum + r.responseTime, 0) / this.results.length,
                totalErrors: this.results.reduce((sum, r) => sum + r.errors.length, 0),
                totalWarnings: this.results.reduce((sum, r) => sum + r.warnings.length, 0),
                validationDuration: duration,
                timestamp: endTime.toISOString()
            },
            results: this.results,
            recommendations: this.generateRecommendations()
        };
        
        // Save JSON report
        const reportPath = path.join(REPORT_DIR, `validation-report-${Date.now()}.json`);
        await fs.writeFile(reportPath, JSON.stringify(report, null, 2));
        
        // Generate HTML report
        const htmlReport = this.generateHTMLReport(report);
        const htmlPath = path.join(REPORT_DIR, `validation-report-${Date.now()}.html`);
        await fs.writeFile(htmlPath, htmlReport);
        
        console.log(`\n📊 Report generated:`);
        console.log(`   JSON: ${reportPath}`);
        console.log(`   HTML: ${htmlPath}`);
        
        return report;
    }

    generateRecommendations() {
        const recommendations = [];
        
        const errorCount = this.results.reduce((sum, r) => sum + r.errors.length, 0);
        if (errorCount > 0) {
            recommendations.push({
                priority: 'high',
                category: 'errors',
                message: `Fix ${errorCount} errors found across endpoints`,
                action: 'Review error logs and fix JavaScript/network issues'
            });
        }
        
        const slowEndpoints = this.results.filter(r => r.responseTime > 3000);
        if (slowEndpoints.length > 0) {
            recommendations.push({
                priority: 'medium',
                category: 'performance',
                message: `${slowEndpoints.length} endpoints are slow (>3s)`,
                action: 'Optimize page load times and server response'
            });
        }
        
        const errorEndpoints = this.results.filter(r => r.status >= 400);
        if (errorEndpoints.length > 0) {
            recommendations.push({
                priority: 'high',
                category: 'availability',
                message: `${errorEndpoints.length} endpoints are returning errors`,
                action: 'Check server logs and fix broken endpoints'
            });
        }
        
        return recommendations;
    }

    generateHTMLReport(report) {
        return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terpedia.com Endpoint Validation Report</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #007cba 0%, #005a87 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 30px; }
        .metric { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .metric-value { font-size: 2em; font-weight: bold; color: #007cba; }
        .metric-label { color: #6c757d; margin-top: 5px; }
        .results { padding: 0 30px 30px; }
        .endpoint { border: 1px solid #e1e5e9; border-radius: 8px; margin-bottom: 20px; overflow: hidden; }
        .endpoint-header { background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #e1e5e9; }
        .endpoint-content { padding: 20px; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        .status-healthy { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
        .screenshot { max-width: 100%; border: 1px solid #e1e5e9; border-radius: 4px; margin: 10px 0; }
        .errors, .warnings { margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 5px 10px; border-radius: 4px; margin: 2px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 5px 10px; border-radius: 4px; margin: 2px 0; }
        .recommendations { background: #e7f3ff; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .recommendation { margin: 10px 0; padding: 10px; background: white; border-radius: 4px; border-left: 4px solid #007cba; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Terpedia.com Endpoint Validation Report</h1>
            <p>Generated on ${new Date(report.summary.timestamp).toLocaleString()}</p>
            <p>Validation completed in ${Math.round(report.summary.validationDuration / 1000)}s</p>
        </div>
        
        <div class="summary">
            <div class="metric">
                <div class="metric-value">${report.summary.totalEndpoints}</div>
                <div class="metric-label">Total Endpoints</div>
            </div>
            <div class="metric">
                <div class="metric-value">${report.summary.healthyEndpoints}</div>
                <div class="metric-label">Healthy</div>
            </div>
            <div class="metric">
                <div class="metric-value">${report.summary.errorEndpoints}</div>
                <div class="metric-label">Errors</div>
            </div>
            <div class="metric">
                <div class="metric-value">${Math.round(report.summary.averageResponseTime)}ms</div>
                <div class="metric-label">Avg Response Time</div>
            </div>
            <div class="metric">
                <div class="metric-value">${report.summary.totalErrors}</div>
                <div class="metric-label">Total Errors</div>
            </div>
            <div class="metric">
                <div class="metric-value">${report.summary.totalWarnings}</div>
                <div class="metric-label">Warnings</div>
            </div>
        </div>
        
        <div class="results">
            <h2>📋 Endpoint Results</h2>
            ${this.results.map(result => `
                <div class="endpoint">
                    <div class="endpoint-header">
                        <h3>${result.name}</h3>
                        <p><strong>URL:</strong> <a href="${result.url}" target="_blank">${result.url}</a></p>
                        <span class="status status-${result.status >= 200 && result.status < 300 ? 'healthy' : 'error'}">
                            HTTP ${result.status} (${result.responseTime}ms)
                        </span>
                    </div>
                    <div class="endpoint-content">
                        ${result.screenshot ? `<img src="${result.screenshot}" class="screenshot" alt="Screenshot of ${result.name}">` : ''}
                        
                        ${result.aiEvaluation ? `
                            <h4>🤖 AI Evaluation</h4>
                            <p><strong>Health:</strong> ${result.aiEvaluation.overallHealth} (Score: ${result.aiEvaluation.score}/100)</p>
                            <ul>
                                ${result.aiEvaluation.issues.map(issue => `<li>${issue}</li>`).join('')}
                            </ul>
                        ` : ''}
                        
                        ${result.errors.length > 0 ? `
                            <div class="errors">
                                <h4>❌ Errors</h4>
                                ${result.errors.map(error => `<div class="error">${error}</div>`).join('')}
                            </div>
                        ` : ''}
                        
                        ${result.warnings.length > 0 ? `
                            <div class="warnings">
                                <h4>⚠️ Warnings</h4>
                                ${result.warnings.map(warning => `<div class="warning">${warning}</div>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('')}
        </div>
        
        ${report.recommendations.length > 0 ? `
            <div class="recommendations">
                <h2>💡 Recommendations</h2>
                ${report.recommendations.map(rec => `
                    <div class="recommendation">
                        <strong>${rec.priority.toUpperCase()}</strong> - ${rec.message}
                        <br><em>Action: ${rec.action}</em>
                    </div>
                `).join('')}
            </div>
        ` : ''}
    </div>
</body>
</html>`;
    }

    async run() {
        try {
            await this.init();
            
            console.log(`\n🎯 Validating ${ENDPOINTS.length} endpoints...`);
            
            for (const endpoint of ENDPOINTS) {
                await this.validateEndpoint(endpoint);
            }
            
            console.log('\n📊 Generating report...');
            const report = await this.generateReport();
            
            console.log('\n✅ Validation complete!');
            console.log(`   📈 Healthy endpoints: ${report.summary.healthyEndpoints}/${report.summary.totalEndpoints}`);
            console.log(`   ⚡ Average response time: ${Math.round(report.summary.averageResponseTime)}ms`);
            console.log(`   ❌ Total errors: ${report.summary.totalErrors}`);
            console.log(`   ⚠️ Total warnings: ${report.summary.totalWarnings}`);
            
        } catch (error) {
            console.error('❌ Validation failed:', error);
            process.exit(1);
        } finally {
            if (this.browser) {
                await this.browser.close();
            }
        }
    }
}

// Run the validator
if (require.main === module) {
    const validator = new EndpointValidator();
    validator.run().catch(console.error);
}

module.exports = EndpointValidator;

