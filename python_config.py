#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Configuração do Python CNIS Extractor para VPS Ubuntu 20.04
Previdia - www.previdia.com.br
"""

import os
import sys
import subprocess
from pathlib import Path

def install_system_dependencies():
    """Instala dependências do sistema necessárias para o Python"""
    print("🔧 Instalando dependências do sistema...")
    
    dependencies = [
        "build-essential",
        "libssl-dev", 
        "libffi-dev",
        "python3-dev",
        "libtesseract-dev",
        "tesseract-ocr",
        "tesseract-ocr-por",
        "libopencv-dev",
        "python3-opencv",
        "libgl1-mesa-glx",
        "libglib2.0-0",
        "libsm6",
        "libxext6",
        "libxrender-dev",
        "libgomp1"
    ]
    
    for dep in dependencies:
        try:
            subprocess.run(["sudo", "apt", "install", "-y", dep], check=True)
            print(f"✅ {dep} instalado")
        except subprocess.CalledProcessError:
            print(f"❌ Erro ao instalar {dep}")

def setup_python_environment():
    """Configura ambiente Python virtual"""
    print("🐍 Configurando ambiente Python...")
    
    # Criar diretório do projeto
    project_dir = Path("/home/previdia")
    project_dir.mkdir(exist_ok=True)
    
    # Criar ambiente virtual
    venv_path = project_dir / "venv"
    if not venv_path.exists():
        subprocess.run(["python3.9", "-m", "venv", str(venv_path)], check=True)
        print("✅ Ambiente virtual criado")
    
    # Ativar ambiente virtual
    activate_script = venv_path / "bin" / "activate_this.py"
    if activate_script.exists():
        with open(activate_script) as f:
            exec(f.read(), {'__file__': str(activate_script)})
    
    return venv_path

def install_python_packages():
    """Instala pacotes Python necessários"""
    print("📦 Instalando pacotes Python...")
    
    packages = [
        "PyMuPDF==1.23.8",
        "pytesseract==0.3.10", 
        "Pillow==10.1.0",
        "opencv-python==4.8.1.78",
        "numpy==1.24.3",
        "transformers==4.35.2",
        "spacy==3.7.2",
        "pandas==2.1.3",
        "torch==2.1.1"
    ]
    
    for package in packages:
        try:
            subprocess.run([sys.executable, "-m", "pip", "install", package], check=True)
            print(f"✅ {package} instalado")
        except subprocess.CalledProcessError:
            print(f"❌ Erro ao instalar {package}")

def download_spacy_model():
    """Baixa modelo do spaCy para português"""
    print("🌍 Baixando modelo spaCy para português...")
    try:
        subprocess.run([sys.executable, "-m", "spacy", "download", "pt_core_news_sm"], check=True)
        print("✅ Modelo spaCy baixado")
    except subprocess.CalledProcessError:
        print("❌ Erro ao baixar modelo spaCy")

def create_cnis_extractor_script():
    """Cria script otimizado para extração CNIS"""
    script_content = '''#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
CNIS Extractor Otimizado para VPS
Previdia - www.previdia.com.br
"""

import os
import sys
import fitz  # PyMuPDF
import pytesseract
import cv2
import numpy as np
import pandas as pd
from PIL import Image
import spacy
import json
from pathlib import Path

# Configurar caminhos
TESSERACT_CMD = '/usr/bin/tesseract'
pytesseract.pytesseract.tesseract_cmd = TESSERACT_CMD

# Carregar modelo spaCy
try:
    nlp = spacy.load("pt_core_news_sm")
except OSError:
    print("Modelo spaCy não encontrado. Execute: python -m spacy download pt_core_news_sm")
    sys.exit(1)

class CNISExtractor:
    def __init__(self):
        self.nlp = nlp
        self.extracted_data = {}
    
    def extract_text_from_pdf(self, pdf_path):
        """Extrai texto do PDF usando PyMuPDF"""
        try:
            doc = fitz.open(pdf_path)
            text = ""
            for page in doc:
                text += page.get_text()
            doc.close()
            return text
        except Exception as e:
            print(f"Erro ao extrair texto do PDF: {e}")
            return ""
    
    def extract_text_from_image(self, image_path):
        """Extrai texto de imagem usando OCR"""
        try:
            # Carregar imagem
            image = cv2.imread(image_path)
            if image is None:
                return ""
            
            # Pré-processamento
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
            thresh = cv2.threshold(gray, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)[1]
            
            # OCR
            text = pytesseract.image_to_string(thresh, lang='por')
            return text
        except Exception as e:
            print(f"Erro no OCR: {e}")
            return ""
    
    def parse_cnis_data(self, text):
        """Analisa texto extraído e identifica dados do CNIS"""
        # Implementar lógica de parsing específica do CNIS
        # Este é um exemplo básico - adapte conforme necessário
        
        data = {
            "nome": "",
            "cpf": "",
            "nis": "",
            "vinculos": []
        }
        
        # Buscar CPF
        import re
        cpf_pattern = r'\d{3}\.\d{3}\.\d{3}-\d{2}'
        cpf_match = re.search(cpf_pattern, text)
        if cpf_match:
            data["cpf"] = cpf_match.group()
        
        # Buscar NIS
        nis_pattern = r'\d{11}'
        nis_matches = re.findall(nis_pattern, text)
        if nis_matches:
            data["nis"] = nis_matches[0]
        
        return data
    
    def process_file(self, file_path):
        """Processa arquivo (PDF ou imagem)"""
        file_path = Path(file_path)
        
        if not file_path.exists():
            raise FileNotFoundError(f"Arquivo não encontrado: {file_path}")
        
        text = ""
        
        if file_path.suffix.lower() == '.pdf':
            text = self.extract_text_from_pdf(str(file_path))
        elif file_path.suffix.lower() in ['.jpg', '.jpeg', '.png', '.tiff']:
            text = self.extract_text_from_image(str(file_path))
        else:
            raise ValueError(f"Formato de arquivo não suportado: {file_path.suffix}")
        
        if text:
            self.extracted_data = self.parse_cnis_data(text)
            return self.extracted_data
        else:
            raise ValueError("Nenhum texto foi extraído do arquivo")
    
    def save_results(self, output_path):
        """Salva resultados em JSON"""
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(self.extracted_data, f, ensure_ascii=False, indent=2)

def main():
    """Função principal"""
    if len(sys.argv) < 2:
        print("Uso: python cnis_extractor.py <arquivo> [output.json]")
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_file = sys.argv[2] if len(sys.argv) > 2 else "cnis_extracted.json"
    
    try:
        extractor = CNISExtractor()
        result = extractor.process_file(input_file)
        extractor.save_results(output_file)
        
        print("✅ Extração concluída com sucesso!")
        print(f"📄 Resultados salvos em: {output_file}")
        print(f"📊 Dados extraídos: {json.dumps(result, ensure_ascii=False, indent=2)}")
        
    except Exception as e:
        print(f"❌ Erro durante a extração: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
'''
    
    script_path = Path("/home/previdia/cnis_extractor.py")
    with open(script_path, 'w', encoding='utf-8') as f:
        f.write(script_content)
    
    # Tornar executável
    os.chmod(script_path, 0o755)
    print(f"✅ Script CNIS Extractor criado: {script_path}")

def create_service_file():
    """Cria arquivo de serviço systemd para o Python"""
    service_content = '''[Unit]
Description=Previdia Python CNIS Extractor
After=network.target

[Service]
Type=simple
User=previdia
Group=www-data
WorkingDirectory=/home/previdia
Environment=PATH=/home/previdia/venv/bin
ExecStart=/home/previdia/venv/bin/python /home/previdia/cnis_extractor.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
'''
    
    service_path = Path("/etc/systemd/system/previdia-python.service")
    with open(service_path, 'w') as f:
        f.write(service_content)
    
    print(f"✅ Arquivo de serviço criado: {service_path}")

def main():
    """Função principal"""
    print("🚀 Configurando Python CNIS Extractor para Previdia")
    print("IP: 177.153.20.195")
    print("Domínio: www.previdia.com.br")
    print()
    
    try:
        # Instalar dependências do sistema
        install_system_dependencies()
        
        # Configurar ambiente Python
        venv_path = setup_python_environment()
        
        # Instalar pacotes Python
        install_python_packages()
        
        # Baixar modelo spaCy
        download_spacy_model()
        
        # Criar script CNIS Extractor
        create_cnis_extractor_script()
        
        # Criar arquivo de serviço
        create_service_file()
        
        print()
        print("✅ Configuração Python concluída com sucesso!")
        print()
        print("📋 Próximos passos:")
        print("1. Ativar serviço: sudo systemctl enable previdia-python")
        print("2. Iniciar serviço: sudo systemctl start previdia-python")
        print("3. Testar extração: python /home/previdia/cnis_extractor.py arquivo.pdf")
        print()
        print("🔧 Comandos úteis:")
        print("- Ver status: sudo systemctl status previdia-python")
        print("- Ver logs: sudo journalctl -u previdia-python -f")
        print("- Reiniciar: sudo systemctl restart previdia-python")
        
    except Exception as e:
        print(f"❌ Erro durante a configuração: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main() 