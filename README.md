# LGMS Module for Drupal 10

Welcome to the LGMS Module installation guide. This document provides step-by-step instructions on how to install the LGMS module on your Drupal 10 site.

## Prerequisites

Before you begin, ensure you have the following installed:
- **Drush**: Drupal's command-line interface tool. [See Drush documentation for installation instructions](https://www.drush.org/).
- **Unzip tool**: Linux and macOS typically come with an unzip tool installed. Windows users can install a tool like [7-Zip](https://www.7-zip.org/download.html).

## Installation Steps

### 1. Download the Module

Download the LGMS module ZIP file from the GitHub repository to your local machine.

### 2. Prepare the Module Directory

Navigate to the Drupal root directory on your system and create a directory for custom modules if it doesn't exist.

```bash
cd /path/to/your/drupal/root
mkdir -p modules/custom
```

### 3. Place the Module

Place the downloaded lgmsmodule.zip file into the modules/custom directory. Then, navigate to this directory:

```bash
cd modules/custom
```

### 4. Unzip the Module

Unzip the downloaded file.

```bash
unzip lgmsmodule.zip
```

After unzipping, if the extracted folder is not named lgmsmodule, rename it accordingly:

```bash
mv incorrect-folder-name lgmsmodule
```

### 5. Installation

#### 5.1 Installing the Module via Drupal UI

Navigate to the Extend page on your Drupal site by visiting /admin/modules. Use the filter box to search for **LGMS**, select the module, and click the install button.

#### 5.2 Installing the Module via Drush

Alternatively, you can install the module using Drush. From the Drupal root directory, run the following command to enable the LGMS module:

```bash
drush en lgmsmodule -y
drush cr
```

This will automatically install, enable and clear your Drupal's site cache after installing the LGMS module without needing to use the Drupal admin UI.
