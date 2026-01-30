# 🖥️ BPO Company Monitoring System

![Python](https://img.shields.io/badge/Python-3776AB?style=for-the-badge&logo=python&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![WebSocket](https://img.shields.io/badge/WebSocket-010101?style=for-the-badge&logo=socket.io&logoColor=white)

A comprehensive real-time PC monitoring system designed for BPO (Business Process Outsourcing) companies to track and monitor computer performance metrics across multiple workstations. The system provides live visualization of CPU usage, memory consumption, disk activity, and other critical performance indicators.

## 📋 Overview

This monitoring system enables IT administrators and managers to oversee the health and performance of computer systems in real-time across the organization. Built with a combination of Python for client-side data collection, PHP for backend session management, and WebSocket for real-time communication, the system ensures optimal computer operation and quick detection of potential issues.

## ✨ Key Features

### 🔍 Real-Time Monitoring
- **Live Performance Metrics** - Track CPU usage, memory consumption, and disk activity in real-time
- **WebSocket Communication** - Instant data updates without page reloads
- **Dynamic Visualization** - Interactive charts and graphs for performance data
- **Multi-System Support** - Monitor multiple computers simultaneously

### 🔐 Security & Authentication
- **User Authentication** - Secure login system with session management
- **Role-Based Access** - Different access levels for administrators and users
- **Session Handling** - PHP-based secure session management
- **Access Control** - Only authenticated users can access the dashboard

### 📊 Performance Analytics
- **Historical Data** - Track performance trends over time
- **Alert System** - Notifications for performance threshold breaches
- **Resource Usage Reports** - Comprehensive system resource analysis
- **Performance Optimization** - Identify bottlenecks and optimization opportunities

### 💻 Client-Server Architecture
- **Client Agent** - Python-based monitoring agent installed on workstations
- **Backend Server** - PHP server for data processing and session management
- **Web Dashboard** - User-friendly interface for monitoring and management
- **Real-Time Updates** - WebSocket-based live data streaming

## 🚀 Technologies Used

### Backend
- **Python** - Client monitoring agent and data collection
- **PHP** - Server-side session handling and data processing
- **WebSocket** - Real-time bidirectional communication

### Frontend
- **HTML5** - Modern web structure
- **CSS3** - Responsive styling
- **JavaScript** - Dynamic updates and WebSocket client
- **Chart Libraries** - Data visualization (Chart.js or similar)

### Additional Tools
- **LaTeX** - Documentation and report generation
- **JSON** - Data exchange format
- **SQL/Database** - Performance data storage

## 📋 Prerequisites

Before installation, ensure you have:

- **Python 3.7+** installed
- **PHP 7.4+** installed
- **Web server** (Apache/Nginx)
- **Modern web browser** (Chrome, Firefox, Edge)
- **Network connectivity** between clients and server
- **Administrator privileges** on client machines

## 🔧 Installation

### Server Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/NeilAlvn/BPO-CompanyMonitoringSystem.git
   cd BPO-CompanyMonitoringSystem
   ```

2. **Install Python dependencies**
   ```bash
   pip install -r requirements.txt
   ```

3. **Configure the backend**
   ```bash
   cd backend
   # Edit configuration files with your database credentials
   # Configure WebSocket server settings
   ```

4. **Set up the web server**
   ```bash
   # For Apache
   sudo cp -r frontend/ /var/www/html/monitoring
   
   # Set proper permissions
   sudo chown -R www-data:www-data /var/www/html/monitoring
   sudo chmod -R 755 /var/www/html/monitoring
   ```

5. **Start the backend services**
   ```bash
   # Start WebSocket server
   python backend/websocket_server.py
   
   # Start data collector (if separate)
   python backend/data_collector.py
   ```

### Client Setup

1. **Navigate to client directory**
   ```bash
   cd client
   ```

2. **Configure client settings**
   ```bash
   # Edit config file with server IP/hostname
   nano config.ini
   ```

3. **Install as a service (Windows)**
   ```bash
   python install_service.py
   ```

4. **Or run manually**
   ```bash
   python monitoring_client.py
   ```

### Building Executable (Optional)

1. **Navigate to build directory**
   ```bash
   cd build
   ```

2. **Create executable**
   ```bash
   python build_executable.py
   ```

3. **Distribute to client machines**
   - Copy the generated executable to target machines
   - Run with administrator privileges

## 📁 Project Structure

```
BPO-CompanyMonitoringSystem/
├── backend/              # Server-side processing
│   ├── websocket_server.py
│   ├── data_processor.php
│   ├── session_handler.php
│   └── database/
├── frontend/             # Web dashboard
│   ├── index.html
│   ├── dashboard.html
│   ├── css/
│   ├── js/
│   └── assets/
├── client/               # Monitoring agent
│   ├── monitoring_client.py
│   ├── system_monitor.py
│   ├── config.ini
│   └── utils/
├── build/                # Build scripts
│   ├── build_executable.py
│   └── setup.py
├── downloads/            # Distribution files
├── ins/                  # Installation scripts
├── requirements.txt      # Python dependencies
└── README.md             # Documentation
```

## 🎯 Usage

### For Administrators

1. **Access the dashboard**
   - Navigate to `http://your-server/monitoring`
   - Log in with admin credentials

2. **Monitor systems**
   - View real-time performance metrics
   - Check system health status
   - Review historical data and trends

3. **Manage alerts**
   - Configure threshold alerts
   - Set up notification preferences
   - View alert history

4. **Generate reports**
   - Export performance reports
   - Analyze system trends
   - Share insights with stakeholders

### For Users

1. **Install the client**
   - Run the installer with admin privileges
   - Configure connection to monitoring server
   - Verify successful connection

2. **Automated monitoring**
   - Client runs as a background service
   - Automatically sends performance data
   - No manual intervention required

## 🔐 Security Features

- **Encrypted Communication** - Secure data transmission between client and server
- **Authentication System** - User login and session management
- **Access Control Lists** - Role-based permissions
- **Audit Logging** - Track all system access and changes
- **Data Privacy** - Compliant with data protection regulations

## 📊 Monitoring Metrics

### System Performance
- CPU usage percentage
- Memory utilization
- Disk I/O operations
- Network activity
- Process information

### Application Metrics
- Running applications
- Application resource usage
- Active window tracking
- Application response times

### System Health
- Temperature monitoring
- Hardware status
- Service availability
- Error logs and warnings

## 🛠️ Configuration

### Server Configuration

Edit `backend/config.php`:
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'monitoring_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('WEBSOCKET_PORT', 8080);
?>
```

### Client Configuration

Edit `client/config.ini`:
```ini
[Server]
host = monitoring.yourcompany.com
port = 8080
secure = true

[Monitoring]
interval = 5
metrics = cpu,memory,disk,network

[Logging]
level = INFO
file = monitoring.log
```

## 📈 Performance Optimization

- **Efficient Data Collection** - Optimized sampling intervals
- **Bandwidth Management** - Compressed data transmission
- **Resource Usage** - Minimal impact on monitored systems
- **Scalability** - Support for hundreds of concurrent clients

## 🔧 Troubleshooting

### Client Issues

**Client not connecting:**
- Check network connectivity
- Verify server address and port
- Ensure firewall allows connection
- Check client logs for errors

**High resource usage:**
- Adjust monitoring interval
- Reduce number of tracked metrics
- Check for software conflicts

### Server Issues

**Dashboard not updating:**
- Verify WebSocket server is running
- Check browser console for errors
- Confirm client connections
- Review server logs

**Performance degradation:**
- Optimize database queries
- Increase server resources
- Implement caching strategies
- Review and tune WebSocket configuration

## 🤝 Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/NewFeature`)
3. Commit your changes (`git commit -m 'Add NewFeature'`)
4. Push to the branch (`git push origin feature/NewFeature`)
5. Open a Pull Request

## 📄 License

This project is available for educational and commercial use. Please review the license file for detailed terms.

## 🚧 Future Enhancements

- [ ] Mobile app for monitoring
- [ ] Advanced analytics with AI/ML
- [ ] Predictive maintenance alerts
- [ ] Integration with ticketing systems
- [ ] Cloud deployment support
- [ ] Docker containerization
- [ ] Multi-language support
- [ ] Enhanced reporting features
- [ ] API for third-party integrations

## 👨‍💻 Developer

**Neil Alvin Medallon**

IT Specialist and AI Engineer specializing in:
- System Monitoring Solutions
- Network Infrastructure
- Real-Time Data Processing
- Performance Optimization

### 📍 Location
Calamba, Laguna, Philippines

### 🔗 Connect With Me
- **GitHub**: [@NeilAlvn](https://github.com/NeilAlvn)
- **LinkedIn**: [Neil Alvin Medallon](https://www.linkedin.com/in/neil-alvin-medallon-456931336/)
- **Email**: neilalvinmedallon@gmail.com

## 📞 Support

For technical support or inquiries:
- 📧 Email: neilalvinmedallon@gmail.com
- 💼 LinkedIn: [Neil Alvin Medallon](https://www.linkedin.com/in/neil-alvin-medallon-456931336/)
- 🐙 GitHub Issues: [Report a bug](https://github.com/NeilAlvn/BPO-CompanyMonitoringSystem/issues)

## 🙏 Acknowledgments

- Built to address real-world BPO monitoring needs
- Inspired by enterprise IT management solutions
- Community feedback and contributions
- Open source libraries and frameworks

## 📚 Documentation

For detailed documentation:
- **User Guide** - See `docs/user_guide.pdf`
- **Admin Manual** - See `docs/admin_manual.pdf`
- **API Reference** - See `docs/api_reference.md`
- **Architecture** - See `docs/architecture.md`

---

⭐ If you find this project useful, please consider giving it a star!

**Made with ❤️ by Neil Alvin Medallon**

© 2026 Neil Alvin Medallon. All rights reserved.
