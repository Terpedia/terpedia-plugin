/**
 * Frontend Camera Interface for Terproduct Creation
 * Handles camera access, tap-to-capture, and real-time AI analysis
 */

class TerpediaFrontendCreator {
    constructor() {
        this.cameraStream = null;
        this.currentFacingMode = 'environment'; // Start with back camera
        this.capturedPhotos = [];
        this.analysisData = null;
        this.isAnalyzing = false;
        
        this.init();
    }
    
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupInterface());
        } else {
            this.setupInterface();
        }
    }
    
    async setupInterface() {
        // Get DOM elements
        this.video = document.getElementById('camera-stream');
        this.canvas = document.getElementById('capture-canvas');
        this.processingIndicator = document.getElementById('processing-indicator');
        this.processingText = document.getElementById('processing-text');
        this.captureOverlay = document.getElementById('capture-overlay');
        this.thumbnailContainer = document.getElementById('capture-thumbnails');
        this.analysisResults = document.getElementById('analysis-results');
        this.productCard = document.getElementById('product-card');
        this.confidenceMeter = document.getElementById('confidence-meter');
        this.analysisStatus = document.getElementById('analysis-status');
        
        // Check if we have the required elements
        if (!this.video || !this.canvas) {
            console.error('Required camera elements not found');
            return;
        }
        
        // Setup camera
        await this.initializeCamera();
        
        // Setup event listeners
        this.setupEventListeners();
        
        console.log('Frontend creator initialized');
    }
    
    async initializeCamera() {
        try {
            // Check if camera is available
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.showError('Camera not available on this device');
                return;
            }
            
            // Request camera permissions
            await this.startCamera();
            
        } catch (error) {
            console.error('Camera initialization error:', error);
            this.showError('Could not access camera. Please allow camera permissions.');
        }
    }
    
    async startCamera() {
        try {
            // Stop existing stream
            if (this.cameraStream) {
                this.cameraStream.getTracks().forEach(track => track.stop());
            }
            
            // Get camera stream
            const constraints = {
                video: {
                    facingMode: this.currentFacingMode,
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                }
            };
            
            this.cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
            this.video.srcObject = this.cameraStream;
            
            // Update status
            this.updateStatus('Camera ready - tap to capture');
            
        } catch (error) {
            console.error('Camera start error:', error);
            this.showError('Failed to start camera: ' + error.message);
        }
    }
    
    setupEventListeners() {
        // Tap to capture
        this.video.addEventListener('click', (e) => this.capturePhoto(e));
        
        // No extra camera controls - streamlined interface
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseCamera();
            } else {
                this.resumeCamera();
            }
        });
    }
    
    async capturePhoto(event) {
        if (this.isAnalyzing) {
            return; // Prevent multiple captures during analysis
        }
        
        try {
            // Visual feedback
            this.video.style.opacity = '0.5';
            setTimeout(() => {
                this.video.style.opacity = '1';
            }, 150);
            
            // Capture the frame
            const imageData = this.captureFrame();
            
            if (imageData) {
                // Add to captured photos
                this.capturedPhotos.push(imageData);
                
                // Add thumbnail
                this.addThumbnail(imageData);
                
                // Start automatic analysis
                await this.analyzePhoto(imageData);
            }
            
        } catch (error) {
            console.error('Capture error:', error);
            this.showError('Failed to capture photo');
        }
    }
    
    captureFrame() {
        try {
            // Set canvas dimensions to match video
            const videoWidth = this.video.videoWidth || this.video.clientWidth;
            const videoHeight = this.video.videoHeight || this.video.clientHeight;
            
            this.canvas.width = videoWidth;
            this.canvas.height = videoHeight;
            
            // Draw video frame to canvas
            const ctx = this.canvas.getContext('2d');
            ctx.drawImage(this.video, 0, 0, videoWidth, videoHeight);
            
            // Get image data as base64
            const imageData = this.canvas.toDataURL('image/jpeg', 0.8);
            
            return imageData;
            
        } catch (error) {
            console.error('Frame capture error:', error);
            return null;
        }
    }
    
    addThumbnail(imageData) {
        const thumbnail = document.createElement('div');
        thumbnail.className = 'thumbnail';
        
        const img = document.createElement('img');
        img.src = imageData;
        img.alt = 'Captured photo';
        
        thumbnail.appendChild(img);
        this.thumbnailContainer.appendChild(thumbnail);
        
        // Make active
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        thumbnail.classList.add('active');
        
        // Auto-scroll to newest
        this.thumbnailContainer.scrollLeft = this.thumbnailContainer.scrollWidth;
    }
    
    async analyzePhoto(imageData) {
        if (this.isAnalyzing) return;
        
        this.isAnalyzing = true;
        this.showProcessing('Analyzing product...');
        this.updateStatus('AI analyzing image...');
        
        try {
            // Send to backend for analysis
            const response = await fetch(terpediaFrontend.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'analyze_frontend_photos',
                    nonce: terpediaFrontend.nonce,
                    photos: JSON.stringify([imageData])
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Display initial analysis results
                await this.displayAnalysisResults(result.data);
                
                // If ingredients detected, automatically analyze terpenes
                if (result.data.ingredients && result.data.ingredients.length > 0) {
                    await this.analyzeTerpenes(result.data.ingredients);
                }
                
                // Auto-save product if analysis is confident enough
                if (result.data.confidence >= 70) {
                    await this.autoSaveProduct();
                }
                
            } else {
                throw new Error(result.data || 'Analysis failed');
            }
            
        } catch (error) {
            console.error('Analysis error:', error);
            this.showError('Analysis failed: ' + error.message);
        } finally {
            this.isAnalyzing = false;
            this.hideProcessing();
        }
    }
    
    async displayAnalysisResults(data) {
        // Store analysis data
        this.analysisData = data;
        
        // Hide placeholder
        const placeholder = this.analysisResults.querySelector('.analysis-placeholder');
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        
        // Show and populate product card
        this.productCard.style.display = 'block';
        
        // Extract structured information from analysis
        const productInfo = this.parseProductInfo(data);
        
        // Update product card
        this.updateProductCard(productInfo);
        
        // Update confidence meter
        this.updateConfidenceMeter(data.confidence || 80);
        
        // Update status
        this.updateStatus(`Analysis complete - ${data.confidence || 80}% confidence`);
    }
    
    parseProductInfo(data) {
        // Parse the analysis results to extract structured product information
        let productName = 'Unknown Product';
        let brand = 'Unknown Brand';
        let productType = 'Unknown';
        let quantity = 'Unknown';
        
        // Try to extract from raw analysis if available
        if (data.raw_analysis && data.raw_analysis.length > 0) {
            const analysis = data.raw_analysis[0].content || '';
            
            // Extract product name
            const nameMatch = analysis.match(/Product Name?:\s*([^\n,]+)/i);
            if (nameMatch) {
                productName = nameMatch[1].trim();
            }
            
            // Extract brand
            const brandMatch = analysis.match(/(?:Brand|Manufacturer):\s*([^\n,]+)/i);
            if (brandMatch) {
                brand = brandMatch[1].trim();
            }
            
            // Extract product type
            const typeMatch = analysis.match(/(?:Product Type|Type|Category):\s*([^\n,]+)/i);
            if (typeMatch) {
                productType = typeMatch[1].trim();
            }
            
            // Extract quantity
            const quantityMatch = analysis.match(/(?:Quantity|Size|Volume|Weight):\s*([^\n,]+)/i);
            if (quantityMatch) {
                quantity = quantityMatch[1].trim();
            }
        }
        
        return {
            name: productName,
            brand: brand,
            type: productType,
            quantity: quantity,
            ingredients: data.ingredients || [],
            terpenes: data.terpenes || []
        };
    }
    
    updateProductCard(productInfo) {
        // Update product header
        document.getElementById('detected-name').textContent = productInfo.name;
        document.getElementById('detected-brand').textContent = productInfo.brand;
        document.getElementById('detected-type').textContent = productInfo.type;
        document.getElementById('detected-quantity').textContent = productInfo.quantity;
        
        // Update ingredients
        const ingredientsContainer = document.getElementById('detected-ingredients');
        ingredientsContainer.innerHTML = '';
        
        if (productInfo.ingredients.length > 0) {
            productInfo.ingredients.forEach(ingredient => {
                const tag = document.createElement('span');
                tag.className = 'ingredient-tag';
                tag.textContent = ingredient;
                ingredientsContainer.appendChild(tag);
            });
        } else {
            ingredientsContainer.innerHTML = '<span class="no-data">No ingredients detected</span>';
        }
        
        // Update terpenes
        const terpenesContainer = document.getElementById('detected-terpenes');
        terpenesContainer.innerHTML = '';
        
        if (productInfo.terpenes.length > 0) {
            productInfo.terpenes.forEach(terpene => {
                const tag = document.createElement('span');
                tag.className = 'terpene-tag';
                tag.textContent = terpene.name + (terpene.concentration ? ` (${terpene.concentration})` : '');
                terpenesContainer.appendChild(tag);
            });
        } else {
            terpenesContainer.innerHTML = '<span class="no-data">Analyzing terpenes...</span>';
        }
    }
    
    async analyzeTerpenes(ingredients) {
        this.showProcessing('Analyzing terpenes...');
        this.updateStatus('Analyzing terpene profile...');
        
        try {
            // Send ingredients for terpene analysis
            const response = await fetch(terpediaFrontend.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'analyze_terpene_profile',
                    nonce: terpediaFrontend.nonce,
                    ingredients: JSON.stringify(ingredients)
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.data.terpenes) {
                // Update terpenes in analysis data
                this.analysisData.terpenes = result.data.terpenes;
                
                // Update display
                const terpenesContainer = document.getElementById('detected-terpenes');
                terpenesContainer.innerHTML = '';
                
                result.data.terpenes.forEach(terpene => {
                    const tag = document.createElement('span');
                    tag.className = 'terpene-tag';
                    tag.textContent = terpene.name + (terpene.concentration ? ` (${terpene.concentration})` : '');
                    terpenesContainer.appendChild(tag);
                });
                
                this.updateStatus(`Terpene analysis complete - ${result.data.terpenes.length} terpenes detected`);
            }
            
        } catch (error) {
            console.error('Terpene analysis error:', error);
            // Don't show error for terpene analysis - it's supplementary
        } finally {
            this.hideProcessing();
        }
    }
    
    async autoSaveProduct() {
        if (!this.analysisData) {
            return;
        }
        
        this.showAutoSaveStatus('saving', '💾 Auto-saving product...');
        
        try {
            const formData = new FormData();
            formData.append('action', 'create_terproduct_frontend');
            formData.append('nonce', terpediaFrontend.nonce);
            
            // Get product info
            const productName = document.getElementById('detected-name').textContent;
            const productBrand = document.getElementById('detected-brand').textContent;
            const productType = document.getElementById('detected-type').textContent;
            
            formData.append('product_title', productName);
            formData.append('product_brand', productBrand);
            formData.append('product_description', `${productType} from ${productBrand}`);
            formData.append('ingredients_list', this.analysisData.ingredients.join(', '));
            formData.append('analysis_data', JSON.stringify(this.analysisData));
            formData.append('photo_data', JSON.stringify(this.capturedPhotos));
            formData.append('confidence_score', this.analysisData.confidence || 80);
            formData.append('user_email', 'anonymous@terpedia.com'); // Default for anonymous users
            formData.append('terms_consent', 'true');
            
            const response = await fetch(terpediaFrontend.ajaxurl, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAutoSaveStatus('success', '✅ Product automatically saved to database!');
                
                // Auto-redirect after success
                setTimeout(() => {
                    window.location.href = result.data.redirect_url;
                }, 3000);
                
            } else {
                throw new Error(result.data || 'Failed to save product');
            }
            
        } catch (error) {
            console.error('Auto-save error:', error);
            this.showAutoSaveStatus('error', '❌ Auto-save failed: ' + error.message);
        }
    }
    
    async switchCamera() {
        this.currentFacingMode = this.currentFacingMode === 'user' ? 'environment' : 'user';
        await this.startCamera();
    }
    
    toggleFlash() {
        // Flash toggle functionality would go here
        // This is device/browser dependent
        console.log('Flash toggle requested');
    }
    
    pauseCamera() {
        if (this.cameraStream) {
            this.cameraStream.getTracks().forEach(track => {
                track.enabled = false;
            });
        }
    }
    
    resumeCamera() {
        if (this.cameraStream) {
            this.cameraStream.getTracks().forEach(track => {
                track.enabled = true;
            });
        }
    }
    
    showProcessing(message) {
        this.processingIndicator.style.display = 'flex';
        this.processingText.textContent = message;
    }
    
    hideProcessing() {
        this.processingIndicator.style.display = 'none';
    }
    
    updateStatus(message) {
        const statusText = this.analysisStatus.querySelector('.status-text');
        if (statusText) {
            statusText.textContent = message;
        }
    }
    
    updateConfidenceMeter(confidence) {
        const meter = this.confidenceMeter;
        const fill = meter.querySelector('.confidence-fill');
        const value = meter.querySelector('.confidence-value');
        
        meter.style.display = 'flex';
        fill.style.width = confidence + '%';
        value.textContent = Math.round(confidence) + '%';
    }
    
    showError(message) {
        const saveStatus = document.getElementById('save-status');
        if (saveStatus) {
            saveStatus.className = 'save-status error';
            saveStatus.textContent = '❌ ' + message;
        }
        
        this.updateStatus('Error: ' + message);
        console.error('Frontend error:', message);
    }
    
    showSuccess(message) {
        const saveStatus = document.getElementById('save-status');
        if (saveStatus) {
            saveStatus.className = 'save-status success';
            saveStatus.textContent = '✅ ' + message;
        }
        
        this.updateStatus(message);
    }
    
    showAutoSaveStatus(type, message) {
        const autoSaveStatus = document.getElementById('auto-save-status');
        if (autoSaveStatus) {
            autoSaveStatus.style.display = 'block';
            autoSaveStatus.className = `auto-save-status ${type}`;
            autoSaveStatus.textContent = message;
            
            // Add show animation
            this.productCard.classList.add('show');
        }
    }
}

// Initialize when DOM is ready
if (typeof terpediaFrontend !== 'undefined') {
    new TerpediaFrontendCreator();
} else {
    console.error('terpediaFrontend configuration not found');
}