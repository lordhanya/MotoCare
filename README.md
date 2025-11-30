**MotoCare**
**Proprietary Vehicle Maintenance Management System**
A smart, automated vehicle maintenance tracker designed to predict upcoming service dates, maintain detailed service history, track expenses, and send automated reminders for all essential vehicle maintenance activities.
---
🚗 **Overview**
MotoCare is a comprehensive, private vehicle maintenance management system designed to help users efficiently track, schedule, and manage their vehicle's health and maintenance records. The system features automated reminders, expense tracking, and predictive maintenance scheduling.

🛠️ Technology Stack
**Backend**
- PHP 8+: Core application logic
- MySQL: Database management
- PHPMailer: Email functionality for automated reminders
- vlucas/phpdotenv: Environment configuration management
**Frontend**
- HTML5: Semantic markup structure
- CSS3: Modern styling with animations and transitions
- JavaScript (ES6+): Interactive functionality and DOM manipulation
- Bootstrap v5.3: Responsive grid system and UI components
- Bootstrap Icon library
  
**DevOps**
- Docker: Containerization support
- Composer: PHP dependency management
- Git: Version control
  
📁 **Project Structure**
**MotoCare/
├── 📄 index.php                    # Main landing page
├── 📄 .gitignore                   # Git ignore rules
├── 📄 composer.json                # PHP dependencies
├── 📄 composer.lock                # Dependency lock file
├── 📄 Dockerfile                   # Docker configuration
│
├── 📁 assets/                      # Static assets
│   ├── 📁 images/                  # Image assets
│   │   ├── motocare_logo.png       # Application logo
│   │   ├── bike_model.png          # Bike model image
│   │   ├── car_model.png           # Car model image
│   │   ├── scooter_model.png       # Scooter model image
│   │   ├── default.jpg             # Default profile picture
│   │   ├── wp12487135-*.jpg       # Background images
│   │   └── p1.jpg - p14.jpg       # Additional UI images
│   ├── 📄 style.css                # Main stylesheet
│   └── 📄 script.js                # JavaScript functionality
│
├── 📁 includes/                    # Core application modules
│   ├── 📁 auth/                    # Authentication modules
│   │   ├── 📄 email_helpers.php    # Email utility functions
│   │   ├── 📄 forgot_password.php  # Password reset flow
│   │   ├── 📄 resend_verification.php # Email verification
│   │   ├── 📄 reset_password.php   # Password reset handler
│   │   └── 📄 verify.php           # Email verification handler
│   ├── 📄 add_maintenance.php      # Maintenance record management
│   ├── 📄 add_vehicle.php          # Vehicle management
│   ├── 📄 dashNav.php              # Dashboard navigation
│   ├── 📄 dashboard.php            # Main dashboard
│   ├── 📄 footer.php               # Footer component
│   ├── 📄 header.php               # Header component
│   ├── 📄 login.php                # User authentication
│   ├── 📄 logout.php               # Session termination
│   ├── 📄 maintenance_list.php    # Maintenance history view
│   ├── 📄 profile.php              # User profile management
│   ├── 📄 register.php             # User registration
│   ├── 📄 reports.php              # Reports and analytics
│   ├── 📄 save_profile.php         # Profile data persistence
│   ├── 📄 schedule_list.php        # Scheduled maintenance view
│   ├── 📄 schedule_maintenance.php # Maintenance scheduling
│   ├── 📄 sidebar.php              # Dashboard sidebar
│   ├── 📄 spinner.php              # Loading component
│   ├── 📄 vehicles.php             # Vehicle listing
│   └── 📄 ResendMailer.php         # Email resend functionality
│
├── 📁 db/                          # Database configuration
│   └── 📄 connection.php           # Database connection handler
│
└── 📁 cron/                        # Scheduled tasks
    ├── 📄 run_reminders.php        # Reminder execution
    └── 📄 send_reminders.php       # Email reminder dispatch**
  
🎯 **Core Features**
Vehicle Management
- Add, edit, and delete vehicles
- Support for cars, bikes, and scooters
- Vehicle photo uploads
- Detailed vehicle information storage
Maintenance Tracking
- Comprehensive maintenance logging
- Service history management
- Cost tracking and expense analysis
- Maintenance type categorization
Automated Reminders
- Email-based service reminders
- Dashboard notifications
- Configurable reminder schedules
- Multiple reminder types (service, insurance, pollution check)
User Management
- Secure user authentication
- Profile management
- Email verification system
- Password reset functionality
Reporting & Analytics
- Maintenance cost reports
- Vehicle health analytics
- Expense tracking by category
- Data export capabilities
  
🔧 **Installation & Setup**
Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependency management)
Environment Configuration
1. Clone the repository to your web server
2. Install PHP dependencies:
      composer install
   3. Configure environment variables:
   - Database connection settings
   - Email configuration (SMTP)
   - Application settings
4. Set up the database using provided schema
5. Configure web server to point to project root
6. Set appropriate file permissions
Docker Deployment
# Build and run with Docker
docker build -t motocare .
docker run -p 80:80 motocare

🎨 **Design System**
Color Palette
- Primary: #f82900 (Orange accent)
- Secondary: #ff4520 (Hover state)
- Background: #000000 (Dark theme)
- Card Background: #0a0a0a
- Text Primary: #ffffff
- Text Secondary: #b0b0b0
- Success: #00ff88
- Warning: #ffd700
- Danger: #ff4444
Typography
- Primary Font: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif
- Headings: Bold weights with tight letter-spacing
- Body Text: Regular weight with optimal line-height
  
📱 **Responsive Design**
Breakpoints
- Desktop: ≥ 1200px
- Laptop: 992px - 1199px
- Tablet: 768px - 991px
- Mobile: 576px - 767px
- Small Mobile: ≤ 575px
  
🔒 **Security Features**
- Secure user authentication with session management
- Input validation and sanitization
- XSS protection
- SQL injection prevention
- Email verification system
- Password reset with secure tokens
  
📊 **Database Schema**
Core Tables
- users: User accounts and authentication
- vehicles: Vehicle information and details
- maintenance: Maintenance records and history
- scheduled_maintenance: Upcoming maintenance tasks
- reminders: Automated reminder configurations

🚀 **Key Modules**
Authentication System (includes/auth/)
- User registration and login
- Email verification
- Password reset functionality
- Session management
Dashboard (includes/dashboard.php)
- Real-time vehicle overview
- Maintenance schedule summary
- Cost tracking widgets
- Quick action buttons
Vehicle Management (includes/vehicles.php)
- CRUD operations for vehicles
- Photo upload functionality
- Vehicle health status
- Maintenance history integration
Maintenance System (includes/maintenance_list.php, includes/schedule_maintenance.php)
- Maintenance logging and tracking
- Automated scheduling
- Cost analysis
- Reminder configuration
  
📧 **Automated Reminders**
Cron Job Configuration
# Run reminders daily at 9 AM
0 9 * * * /usr/bin/php /path/to/motocare/cron/run_reminders.php
Reminder Types
- Service due reminders
- Insurance renewal alerts
- Pollution check notifications
- Custom maintenance reminders

🌐 **API Integration**
Email Service
- PHPMailer integration for SMTP
- Template-based email system
- Bulk email capabilities
- Email tracking and analytics
External Services
- Formspree integration for contact forms
- Optional third-party API hooks
- Webhook support for integrations
📈 Performance Optimizations
- Optimized database queries
- Efficient file handling
- Minimal external dependencies
- Responsive image loading
- CSS and JavaScript minification

🔧 **Maintenance & Support**
Regular Tasks
- Database backups
- Log file monitoring
- Security updates
- Performance monitoring
Troubleshooting
- Error logging system
- Debug mode configuration
- Performance profiling tools
---
© 2025 MotoCare - All Rights Reserved  
Proprietary Vehicle Maintenance Management System  
Developed by: Ashif (lordhanya)  
Contact: ashifrahman8638471722@gmail.com
