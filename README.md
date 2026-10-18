# KernelTech Solutions | SysOS Interactive Portal & Server Dashboard

Plataforma interactiva para la capacitación de ingenieros junior en Sistemas Operativos y monitoreo en tiempo real del servidor anfitrión, desplegada en una arquitectura web sobre **Ubuntu Server 26.04 LTS** y **Apache HTTP Server (Apache2)**.

---

## 📌 Arquitectura y Tecnologías
- **Sistema Operativo:** Ubuntu Server 26.04 LTS
- **Servidor Web:** Apache HTTP Server (Apache2)
- **Backend / Lenguaje:** PHP (CLI Engine via `libapache2-mod-php`)
- **Seguridad Perimetral:** UFW (Uncomplicated Firewall)
- **Dominio Virtual Local:** `sysos.local`

---

## 🚀 Guía de Despliegue en CLI (Ubuntu Server)

### 1. Actualización de repositorios e instalación de dependencias
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 php libapache2-mod-php -y
