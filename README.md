# Joomla Question & Answer Component

A comprehensive Joomla 6 component that provides a full-featured Question & Answer platform inspired by Yahoo Answers. This component implements modern web standards, real-time updates, and robust security features.

## 🚀 Features

### Core Functionality
- **Question & Answer System** - Complete Q&A platform with voting and best answer selection
- **Multi-language Support** - Extensible language system with Joomla core integration
- **Category Management** - Uses Joomla core categories with nested support
- **Real-time Updates** - Hybrid live update system with SSE, AJAX long polling, and timed polling
- **User Roles & Permissions** - Granular ACL system for different user types
- **Voting System** - Up/down voting with duplicate prevention
- **Search & Filtering** - Advanced search with category and language filters
- **Tag System** - Question tagging for better organization

### Technical Features
- **Joomla 6 Compatible** - Uses namespaced CMS classes and modern PHP 8.2+
- **MVC Architecture** - Proper Joomla MVC implementation for both site and admin
- **Bootstrap 5 Compatible** - Responsive, accessible UI components
- **Security Hardened** - Rate limiting, input sanitization, CSRF protection
- **Database Optimized** - Proper indexing and foreign key constraints
- **Live Update System** - Automatic fallback between SSE, long polling, and timed polling

## 📋 Requirements

- **Joomla 6.0+** 
- **PHP 8.2+**
- **MySQL 8.0+** or MariaDB 10.4+
- **Web server with mod_rewrite** (Apache/Nginx)

## 🛠️ Installation

### Method 1: Joomla Extension Manager
1. Download the component package
2. Navigate to `System` → `Install` → `Extensions`
3. Upload the package file
4. Follow the installation wizard

### Method 2: Manual Installation
1. Extract the package contents
2. Copy files to your Joomla installation:
   ```
   /administrator/components/com_question/
   /components/com_question/
   /media/com_question/
   /language/en-GB/en-GB.com_question.ini
   ```
3. Install via Joomla Extension Manager using the `com_question.xml` manifest

### Post-Installation Setup
1. **Configure Component**: Navigate to `Components` → `Question` → `Configuration`
2. **Set Permissions**: Adjust ACL settings in `System` → `Users` → `Groups`
3. **Create Categories**: Add question categories under `Components` → `Categories`
4. **Enable Languages**: Configure supported languages in component settings

## 🏗️ Architecture

### Directory Structure
```
com_question/
├── com_question.xml                 # Component manifest
├── script.php                       # Installation script
├── admin/
│   ├── sql/
│   │   ├── install.mysql.utf8.sql   # Installation SQL
│   │   └── uninstall.mysql.utf8.sql # Uninstallation SQL
│   └── language/
│       └── en-GB/
│           └── en-GB.com_question.ini
├── administrator/
│   └── components/
│       └── com_question/
│           ├── src/
│           │   ├── Controller/      # Backend controllers
│           │   ├── Model/          # Backend models
│           │   ├── View/           # Backend views
│           │   └── Helper/         # Helper classes
│           └── language/
│               └── en-GB/
│                   └── en-GB.com_question.ini
├── components/
│   └── com_question/
│       ├── src/
│       │   ├── Controller/         # Frontend controllers
│       │   ├── Model/             # Frontend models
│       │   └── View/              # Frontend views
│       └── language/
│           └── en-GB/
│               └── en-GB.com_question.ini
└── media/
    └── com_question/
        ├── css/
        │   └── question.css        # Component styles
        └── js/
            ├── live-update.js      # Live update controller
            └── question.js         # Main frontend JS
```

### Database Tables
- `#__question_questions` - Main questions table
- `#__question_answers` - Answers with best answer flag
- `#__question_votes` - Voting records with duplicate prevention
- `#__question_languages` - Multi-language support
- `#__question_events` - Live update events queue

## 👥 User Roles & Permissions

### Guest Users
- Browse questions and answers
- Filter by category and language
- Search questions

### Registered Users
- All guest permissions
- Submit questions and answers
- Vote on questions and answers
- Follow questions
- Report inappropriate content

### Authors
- All registered user permissions
- Edit and delete own content
- Block users from their questions

### Moderators
- All author permissions
- Moderate all content
- Mark best answers
- Access live activity monitor

### Administrators
- Full system access
- Component configuration
- User management
- System statistics

## 🔧 Configuration

### Component Settings
Navigate to `Components` → `Question` → `Configuration`

**General Settings:**
- Enable/disable live updates
- Set default language
- Configure question limits
- Enable/disable voting

**Security Settings:**
- Rate limiting thresholds
- Minimum reputation for actions
- Content moderation settings

**Display Settings:**
- Questions per page
- Enable/disable tags
- Default sort order

### ACL Configuration
Navigate to `System` → `Users` → `Groups` → `Edit Group`

**Custom Actions:**
- `ask.question` - Submit questions
- `answer.question` - Submit answers
- `vote.answer` - Vote on content
- `moderate.content` - Moderate content
- `view.live.updates` - Access live updates

## 🌐 Live Update System

The component features a hybrid live update system that automatically adapts to the user's environment:

### Connection Methods
1. **Server-Sent Events (SSE)** - Primary method for modern browsers
2. **AJAX Long Polling** - Fallback for browsers without SSE support
3. **AJAX Timed Polling** - Final fallback method

### Event Types
- `new_answer` - New answer posted
- `vote_update` - Vote count changed
- `question_update` - Question edited
- `moderation_update` - Content moderated
- `best_answer` - Best answer selected

### Implementation
The system automatically detects browser capabilities and silently downgrades to the best available method. Users see a live indicator showing the current connection type.

## 🎨 Frontend Features

### Question List Page
- Filterable question grid
- Search functionality
- Category and language filters
- Pagination
- Sort options (newest, popular, unanswered)

### Question Detail Page
- Full question display with voting
- Real-time answer updates
- Answer submission form
- Best answer highlighting
- Related questions
- Social sharing buttons

### Ask Question Page
- Multi-step question submission
- Language and category selection (required)
- Rich text editor support
- Tag suggestions
- Preview functionality

### User Dashboard
- User's questions and answers
- Reputation points
- Following list
- Activity history

## 🛡️ Security Features

### Input Validation
- Server-side validation for all inputs
- XSS protection with output escaping
- SQL injection prevention
- File upload restrictions

### Rate Limiting
- Question submission limits
- Answer posting limits
- Voting frequency limits
- API request throttling

### Access Control
- Token-based form validation
- Session-based authentication
- Granular permission system
- IP-based restrictions

### Content Security
- Automatic content sanitization
- Spam detection
- Profanity filtering
- Malicious link detection

## 📊 Performance Optimization

### Database Optimization
- Proper indexing on all tables
- Query optimization
- Connection pooling
- Caching strategies

### Frontend Optimization
- Lazy loading for images
- Minified CSS/JS files
- Browser caching headers
- CDN-friendly asset structure

### Server Optimization
- Opcode caching
- Database query caching
- Session optimization
- Load balancing ready

## 🔌 API Endpoints

### AJAX Endpoints
- `POST /index.php?option=com_question&task=ajax.vote` - Vote on content
- `POST /index.php?option=com_question&task=ajax.submitAnswer` - Submit answer
- `POST /index.php?option=com_question&task=ajax.submitQuestion` - Submit question
- `GET /index.php?option=com_question&task=ajax.poll` - Poll for updates

### SSE Endpoint
- `GET /index.php?option=com_question&task=sse.stream` - Server-Sent Events stream

### REST API Support
The component is built with REST API compatibility and can be extended for mobile app integration.

## 🧪 Testing

### Unit Tests
```bash
# Run backend model tests
phpunit administrator/components/com_question/tests/Model/

# Run frontend model tests
phpunit components/com_question/tests/Model/
```

### Integration Tests
```bash
# Run controller tests
phpunit administrator/components/com_question/tests/Controller/

# Run live update tests
phpunit components/com_question/tests/Controller/
```

### Browser Testing
- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## 🐛 Troubleshooting

### Common Issues

**Live updates not working:**
- Check browser console for JavaScript errors
- Verify server supports SSE or CORS
- Check firewall/proxy settings
- Ensure component permissions are set correctly

**Questions not appearing:**
- Verify database tables exist
- Check category permissions
- Ensure user has appropriate ACL rights
- Check component configuration

**Voting not working:**
- Verify user is logged in (if required)
- Check rate limiting settings
- Ensure voting permissions are set
- Check JavaScript console for errors

### Debug Mode
Enable debug mode in Joomla configuration to see detailed error messages and SQL queries.

### Log Files
Check Joomla logs at `/logs/` for component-specific error messages.

## 📝 Development

### Adding New Features
1. Follow Joomla MVC patterns
2. Use namespaced classes
3. Implement proper ACL checks
4. Add language strings
5. Update database schema if needed
6. Write tests for new functionality

### Coding Standards
- Follow Joomla Coding Standards
- Use PHP 8.2+ features
- Implement PSR-12 formatting
- Add proper documentation
- Use type hints where possible

### Contributing
1. Fork the repository
2. Create feature branch
3. Make changes following standards
4. Add tests for new features
5. Submit pull request

## 📚 Documentation

### API Documentation
- [Live Update API](docs/live-update-api.md)
- [AJAX Endpoints](docs/ajax-endpoints.md)
- [Database Schema](docs/database-schema.md)

### User Guides
- [User Manual](docs/user-manual.md)
- [Administrator Guide](docs/admin-guide.md)
- [Developer Guide](docs/developer-guide.md)

### Tutorials
- [Getting Started](docs/getting-started.md)
- [Customization Guide](docs/customization.md)
- [Integration Examples](docs/integration.md)

## 🔄 Updates & Maintenance

### Version History
- **1.0.0** - Initial release with core functionality
- **1.1.0** - Added live update system
- **1.2.0** - Enhanced security features
- **1.3.0** - Performance optimizations

### Update Process
1. Backup your site
2. Download new version
3. Install via Extension Manager
4. Review configuration changes
5. Test functionality

### Maintenance Tasks
- Regular database optimization
- Log file cleanup
- Security updates
- Performance monitoring

## 🤝 Support

### Getting Help
- [Joomla Forums](https://forum.joomla.org/)
- [GitHub Issues](https://github.com/your-repo/com_question/issues)
- [Documentation Wiki](https://wiki.github.com/your-repo/com_question)

### Reporting Bugs
1. Check existing issues
2. Create detailed bug report
3. Include system information
4. Provide reproduction steps
5. Add error logs if available

### Feature Requests
Submit feature requests through GitHub issues with detailed requirements and use cases.

## 📄 License

This component is licensed under the GNU General Public License v2.0 or later.

## 🙏 Credits

- **Joomla Project** - Core framework and libraries
- **Bootstrap Team** - UI framework
- **Contributors** - Community feedback and contributions
- **Translators** - Multi-language support

## 🌟 Acknowledgments

Special thanks to the Joomla community for inspiration, feedback, and testing support during the development of this component.

---

**Note**: This component is designed to be a first-class Joomla citizen, following all best practices and coding standards. It's production-ready and suitable for enterprise deployments.
