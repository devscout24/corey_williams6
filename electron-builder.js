const fs = require('fs');

// Start with the base configuration
let config = require('./vendor/nativephp/electron/resources/js/electron-builder.js');

// Add the NSIS configuration for a Windows installer
config.win = {
    "target": [
        {
            "target": "nsis",
            "arch": [
                "x64"
            ]
        }
    ]
};

// The NSIS configuration
config.nsis = {
    "oneClick": false, // Allow user to choose installation directory
    "allowToChangeInstallationDirectory": true,
    "installerIcon": "build/installerIcon.ico",
    "uninstallerIcon": "build/uninstallerIcon.ico",
    "installerHeaderIcon": "build/installerHeaderIcon.ico",
    "createDesktopShortcut": true,
    "createStartMenuShortcut": true
};

module.exports = config;
