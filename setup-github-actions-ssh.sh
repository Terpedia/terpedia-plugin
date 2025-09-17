#!/bin/bash

# Setup script for GitHub Actions SSH deployment
# This script helps configure SSH keys for automated deployment

echo "🔧 Setting up GitHub Actions SSH deployment for Terpedia Plugin"
echo "================================================================"

# Server details
SSH_HOST="giow1015.siteground.us"
SSH_PORT="18765"
SSH_USER="u1298-7ed7rj7u8z97"
KEY_NAME="terpedia_github_actions"

echo ""
echo "📋 Server Details:"
echo "  Host: $SSH_HOST"
echo "  Port: $SSH_PORT"
echo "  User: $SSH_USER"
echo ""

# Check if keys exist
if [ ! -f ~/.ssh/${KEY_NAME} ] || [ ! -f ~/.ssh/${KEY_NAME}.pub ]; then
    echo "❌ SSH keys not found. Please run the key generation first."
    exit 1
fi

echo "✅ SSH keys found:"
echo "  Private: ~/.ssh/${KEY_NAME}"
echo "  Public:  ~/.ssh/${KEY_NAME}.pub"
echo ""

# Display the public key
echo "🔑 PUBLIC KEY (copy this to server authorized_keys):"
echo "=================================================="
cat ~/.ssh/${KEY_NAME}.pub
echo "=================================================="
echo ""

# Display the private key
echo "🔐 PRIVATE KEY (copy this to GitHub secrets as SSH_PRIVATE_KEY):"
echo "=============================================================="
cat ~/.ssh/${KEY_NAME}
echo "=============================================================="
echo ""

echo "📝 Next Steps:"
echo "=============="
echo ""
echo "1. Add the PUBLIC KEY to the server:"
echo "   ssh -p $SSH_PORT $SSH_USER@$SSH_HOST"
echo "   mkdir -p ~/.ssh"
echo "   echo '$(cat ~/.ssh/${KEY_NAME}.pub)' >> ~/.ssh/authorized_keys"
echo "   chmod 600 ~/.ssh/authorized_keys"
echo "   chmod 700 ~/.ssh"
echo ""
echo "2. Add the PRIVATE KEY to GitHub repository secrets:"
echo "   Go to: https://github.com/Terpedia/terpedia-plugin/settings/secrets/actions"
echo "   Add secret: SSH_PRIVATE_KEY"
echo "   Value: (paste the entire private key content above)"
echo ""
echo "3. Test the connection:"
echo "   ssh -i ~/.ssh/${KEY_NAME} -p $SSH_PORT $SSH_USER@$SSH_HOST 'echo \"SSH connection successful!\"'"
echo ""
echo "4. Trigger the deployment workflow:"
echo "   gh workflow run deploy.yml --repo Terpedia/terpedia-plugin"
echo ""

# Test current connection if possible
echo "🧪 Testing current SSH connection..."
if ssh -o ConnectTimeout=10 -o BatchMode=yes -p $SSH_PORT $SSH_USER@$SSH_HOST "echo 'Current SSH connection works'" 2>/dev/null; then
    echo "✅ Current SSH connection is working"
else
    echo "⚠️ Current SSH connection failed - you'll need to add the new public key"
fi

echo ""
echo "🎯 Setup complete! Follow the steps above to configure the keys."


