# DecorVista - Home Interior Design Web Application

A modern, responsive web application for home interior design featuring a glassmorphism UI with purple, black, and grey color scheme. Built with PHP and MySQL using MySQLi for database operations.

## Features

### For Homeowners
- **Browse Products**: Explore furniture, lighting, decor, and more
- **Add to Cart**: Shopping cart functionality with quantity management
- **Browse Gallery**: View inspiration images categorized by room type and style
- **Designer Profiles**: View and book appointments with interior designers
- **Save Favorites**: Save favorite products and gallery images
- **User Profile**: Manage personal information and preferences
- **Order History**: View previous orders and their status
- **Reviews**: Rate and review products and designers

### For Interior Designers
- **Profile Management**: Create and update professional profiles
- **Portfolio Showcase**: Upload and manage portfolio images
- **Appointment Management**: Set availability and manage bookings
- **Client Interaction**: View appointment details and client information
- **Review Management**: View and respond to client reviews

### For Administrators
- **Product Management**: Add, edit, and delete products
- **Order Management**: View and manage customer orders
- **Gallery Management**: Manage inspiration gallery content
- **Contact Management**: View and respond to contact inquiries
- **User Management**: Manage user accounts and roles
- **Appointment Oversight**: Monitor designer appointments
- **Review Moderation**: Approve and moderate user reviews

## Technology Stack

- **Frontend**: HTML5, CSS3, Tailwind CSS, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+ with MySQLi
- **Design**: Glassmorphism UI with responsive mobile-first design
- **Icons**: Font Awesome 6.4.0
- **Fonts**: Google Fonts (Playfair Display, Source Sans Pro)

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- XAMPP/WAMP/LAMP (for local development)

### Setup Instructions

1. **Clone or Download the Project**
   \`\`\`bash
   git clone <repository-url>
   cd decorvista
   \`\`\`

2. **Database Setup**
   - Create a new MySQL database named `decorvista`
   - Import the database schema:
   \`\`\`bash
   mysql -u root -p decorvista < database/decorvista.sql
   \`\`\`

3. **Configuration**
   - Update database credentials in `config/database.php`:
   \`\`\`php
   private $host = 'localhost';
   private $username = 'your_username';
   private $password = 'your_password';
   private $database = 'decorvista';
   \`\`\`

4. **File Permissions**
   - Ensure the `uploads/` directory is writable:
   \`\`\`bash
   chmod 755 uploads/
   \`\`\`

5. **Web Server Configuration**
   - Point your web server document root to the project directory
   - Ensure PHP is enabled and configured properly

### Default Admin Credentials
- **Username**: admin
- **Email**: admin@decorvista.com
- **Password**: password (change immediately after first login)

## Project Structure

\`\`\`
decorvista/
├── config/
│   ├── database.php          # Database connection class
│   └── config.php           # Application configuration
├── includes/
│   ├── header.php           # Common header with navigation
│   └── footer.php           # Common footer
├── database/
│   └── decorvista.sql       # Complete database schema
├── uploads/                 # File upload directory
│   ├── products/
│   ├── gallery/
│   └── profiles/
├── api/                     # AJAX API endpoints
├── admin/                   # Admin panel files
├── designer/                # Designer dashboard files
├── assets/                  # CSS, JS, images
└── README.md               # This file
\`\`\`

## Database Schema

### Core Tables
- **users**: User authentication and basic info
- **user_details**: Extended user profile information
- **interior_designers**: Designer-specific information
- **products**: Product catalog
- **categories**: Product categories (hierarchical)
- **cart**: Shopping cart items
- **orders**: Order management
- **consultations**: Designer appointment bookings
- **reviews**: Product and designer reviews
- **gallery**: Inspiration image gallery
- **favorites**: User saved items

### Key Relationships
- Users can have multiple roles (homeowner, designer, admin)
- Products belong to categories (many-to-many relationship)
- Users can book consultations with designers
- Reviews can be for products or designers
- Users can save favorite products and gallery items

## Security Features

- **Password Hashing**: Using PHP's `password_hash()` function
- **CSRF Protection**: Token-based CSRF protection for forms
- **Input Sanitization**: All user inputs are sanitized and escaped
- **Session Management**: Secure session handling with timeout
- **Role-based Access**: Different access levels for different user types
- **File Upload Security**: Restricted file types and size limits

## Responsive Design

The application features a mobile-first responsive design with:
- **Glassmorphism Effects**: Translucent backgrounds with blur effects
- **Purple/Black/Grey Theme**: Consistent color scheme throughout
- **Hover Animations**: Smooth transitions and glass effects on interaction
- **Mobile Navigation**: Collapsible mobile menu
- **Touch-friendly**: Optimized for touch devices

## API Endpoints

### Cart Management
- `POST /api/add-to-cart.php` - Add product to cart
- `GET /api/cart-count.php` - Get cart item count
- `POST /api/update-cart.php` - Update cart quantities
- `POST /api/remove-from-cart.php` - Remove item from cart

### Favorites
- `POST /api/add-favorite.php` - Add item to favorites
- `POST /api/remove-favorite.php` - Remove from favorites

### Reviews
- `POST /api/submit-review.php` - Submit product/designer review
- `GET /api/get-reviews.php` - Fetch reviews for item

## User Roles and Permissions

### Homeowner
- Browse products and gallery
- Manage shopping cart and orders
- Book designer consultations
- Save favorites and write reviews
- Manage personal profile

### Designer
- Manage professional profile and portfolio
- Set availability for consultations
- View and manage appointments
- Respond to reviews

### Admin
- Full system access
- Manage all users, products, and content
- View system analytics and reports
- Moderate reviews and content

## Customization

### Styling
- Modify CSS variables in `includes/header.php` for color scheme changes
- Update Tailwind classes for layout modifications
- Glassmorphism effects can be adjusted in the CSS

### Configuration
- Update `config/config.php` for application settings
- Modify upload limits and file types as needed
- Adjust pagination and display settings

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists and is accessible

2. **File Upload Issues**
   - Check `uploads/` directory permissions
   - Verify PHP upload settings (`upload_max_filesize`, `post_max_size`)
   - Ensure directory structure exists

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory permissions
   - Clear browser cookies if needed

4. **Styling Issues**
   - Ensure Tailwind CSS is loading properly
   - Check for CSS conflicts
   - Verify Google Fonts are accessible

## Development Notes

- Built with mobile-first responsive design principles
- Uses MySQLi prepared statements for security
- Implements proper error handling and logging
- Follows PHP best practices and coding standards
- Includes comprehensive input validation

## Future Enhancements

- Payment gateway integration
- Real-time chat with designers
- Advanced search and filtering
- Email notifications
- Social media integration
- Multi-language support
- Progressive Web App (PWA) features

## Support

For technical support or questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Ensure all prerequisites are met

## License

This project is developed for educational purposes. Please ensure proper licensing for production use.

---

**DecorVista** - Transform your living spaces with style and elegance.
