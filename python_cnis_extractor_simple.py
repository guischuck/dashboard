#!/usr/bin/env python3
"""
Extrator de dados do CNIS usando Python - Versão Simplificada
Usa apenas bibliotecas básicas do Python para extrair informações do CNIS
"""

import sys
import json
import re
import argparse
from pathlib import Path
from typing import Dict, List, Any, Optional
import logging

# Configuração de logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class CNISExtractorSimple:
    """Classe para extração de dados do CNIS usando Python básico"""
    
    def __init__(self):
        """Inicializa o extrator"""
        logger.info("CNIS Extractor Simple inicializado")
    
    def extract_text_from_pdf(self, pdf_path: str) -> str:
        """Extrai texto do PDF usando métodos básicos"""
        try:
            # Tenta usar PyPDF2 se disponível
            try:
                import PyPDF2
                with open(pdf_path, 'rb') as file:
                    reader = PyPDF2.PdfReader(file)
                    text = ""
                    for page in reader.pages:
                        text += page.extract_text() + "\n"
                logger.info("Texto extraído com PyPDF2")
                return text
            except ImportError:
                logger.warning("PyPDF2 não encontrado, tentando pdfplumber")
            
            # Tenta usar pdfplumber se disponível
            try:
                import pdfplumber
                text = ""
                with pdfplumber.open(pdf_path) as pdf:
                    for page in pdf.pages:
                        page_text = page.extract_text()
                        if page_text:
                            text += page_text + "\n"
                logger.info("Texto extraído com pdfplumber")
                return text
            except ImportError:
                logger.warning("pdfplumber não encontrado")
            
            # Se nenhuma biblioteca estiver disponível, retorna erro
            raise ImportError("Nenhuma biblioteca de PDF encontrada. Instale PyPDF2 ou pdfplumber")
            
        except Exception as e:
            logger.error(f"Erro ao extrair texto do PDF: {e}")
            return ""
    
    def extract_personal_data(self, text: str) -> Dict[str, str]:
        """Extrai dados pessoais do texto"""
        personal_data = {}
        
        # Padrões para CPF
        cpf_patterns = [
            r'CPF[:\s]*(\d{3}\.\d{3}\.\d{3}-\d{2})',
            r'NIT[:\s]*\d+\.\d+\s+CPF[:\s]*(\d{3}\.\d{3}\.\d{3}-\d{2})',
            r'(\d{3}\.\d{3}\.\d{3}-\d{2})'
        ]
        
        for pattern in cpf_patterns:
            match = re.search(pattern, text)
            if match:
                personal_data['cpf'] = match.group(1)
                break
        
        # Padrões para nome
        nome_patterns = [
            r'CPF[:\s]*\d{3}\.\d{3}\.\d{3}-\d{2}\s+Nome[:\s]*([^\n\r]+)',
            r'Nome[:\s]*([^\n\r]+)',
            r'CPF[:\s]*\d{3}\.\d{3}\.\d{3}-\d{2}\s+([A-Z\s]+)'
        ]
        
        for pattern in nome_patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                nome = match.group(1).strip()
                # Remove caracteres especiais e números
                nome = re.sub(r'[0-9\-\_\.]', '', nome)
                nome = nome.strip()
                if len(nome) > 3 and not nome.isdigit():
                    personal_data['nome'] = nome
                    break
        
        # Padrões para data de nascimento
        nasc_patterns = [
            r'Data de nascimento[:\s]*(\d{2}/\d{2}/\d{4})',
            r'Nascimento[:\s]*(\d{2}/\d{2}/\d{4})'
        ]
        
        for pattern in nasc_patterns:
            match = re.search(pattern, text)
            if match:
                personal_data['data_nascimento'] = match.group(1)
                break
        
        return personal_data
    
    def extract_employment_data(self, text: str) -> List[Dict[str, str]]:
        """Extrai dados de vínculos empregatícios"""
        employments = []
        
        # Divide o texto em seções
        sections = self.split_into_employment_sections(text)
        
        for section in sections:
            employment = self.extract_employment_from_section(section)
            if employment and employment.get('empregador'):
                employments.append(employment)
        
        return employments
    
    def split_into_employment_sections(self, text: str) -> List[str]:
        """Divide o texto em seções de vínculos empregatícios"""
        sections = []
        lines = text.split('\n')
        current_section = []
        in_employment_section = False
        
        for line in lines:
            line = line.strip()
            
            # Identifica início de uma seção de vínculo
            if (re.match(r'^Código Emp\.', line) or 
                re.match(r'^\d+\s+\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}', line) or
                re.match(r'^\d+\s+AGRUPAMENTO', line)):
                
                if current_section:
                    sections.append('\n'.join(current_section))
                
                current_section = [line]
                in_employment_section = True
            elif in_employment_section:
                current_section.append(line)
                
                # Identifica fim da seção
                if (re.match(r'^Relações Previdenciárias', line) or
                    re.match(r'^Valores Consolidados', line)):
                    sections.append('\n'.join(current_section))
                    current_section = []
                    in_employment_section = False
        
        if current_section:
            sections.append('\n'.join(current_section))
        
        return sections
    
    def extract_employment_from_section(self, section: str) -> Optional[Dict[str, str]]:
        """Extrai dados de um vínculo empregatício de uma seção"""
        employment = {
            'empregador': '',
            'cnpj': '',
            'data_inicio': '',
            'data_fim': '',
            'salario': '',
            'ultima_remuneracao': ''
        }
        
        lines = section.split('\n')
        
        for line in lines:
            line = line.strip()
            
            # Extrai empregador e CNPJ
            empregador_match = re.match(r'^\d+\s+(\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2})\s+(.+)$', line)
            if empregador_match:
                employment['cnpj'] = empregador_match.group(1)
                employment['empregador'] = empregador_match.group(2).strip()
            
            agrupamento_match = re.match(r'^\d+\s+(AGRUPAMENTO.+)$', line)
            if agrupamento_match:
                empregador = agrupamento_match.group(1).strip()
                # Remove "Contribuinte Individual" e CNPJ do nome
                empregador = re.sub(r'\tContribuinte Individual.*$', '', empregador)
                empregador = re.sub(r'\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}', '', empregador)
                employment['empregador'] = empregador.strip()
                employment['cnpj'] = ''
            
            # Extrai datas
            date_patterns = [
                (r'(\d{2}/\d{2}/\d{4})\s+(\d{2}/\d{2}/\d{4})', 2),  # Duas datas completas
                (r'(\d{2}/\d{2}/\d{4})\s+(\d{2}/\d{4})', 1),       # Data completa + MM/YYYY
                (r'(\d{2}/\d{2}/\d{4})', 0)                        # Uma data
            ]
            
            for pattern, date_count in date_patterns:
                match = re.search(pattern, line)
                if match:
                    if date_count == 2:
                        employment['data_inicio'] = match.group(1)
                        employment['data_fim'] = match.group(2)
                    elif date_count == 1:
                        employment['data_inicio'] = match.group(1)
                        employment['data_fim'] = self.convert_month_year_to_full_date(match.group(2))
                    else:
                        if not employment['data_inicio']:
                            employment['data_inicio'] = match.group(1)
                        elif not employment['data_fim']:
                            employment['data_fim'] = match.group(1)
                    break
            
            # Extrai última remuneração
            ult_rem_match = re.search(r'Últ\. Remun\.\s*(\d{2}/\d{4})', line)
            if ult_rem_match:
                employment['ultima_remuneracao'] = ult_rem_match.group(1)
        
        # Define data de fim se não encontrada
        if not employment['data_fim']:
            employment['data_fim'] = 'sem data fim'
        
        # Extrai salário
        employment['salario'] = self.extract_salary_from_section(section)
        
        return employment
    
    def extract_salary_from_section(self, section: str) -> str:
        """Extrai salário de uma seção"""
        lines = section.split('\n')
        last_salary = ''
        
        for line in lines:
            line = line.strip()
            
            # Procura por remunerações no formato MM/YYYY VALOR
            salary_match = re.search(r'\d{2}/\d{4}\s+([\d\.,]+)', line)
            if salary_match:
                salary = salary_match.group(1)
                # Converte para formato numérico
                salary = salary.replace('.', '').replace(',', '.')
                try:
                    salary_float = float(salary)
                    last_salary = f"{salary_float:,.2f}".replace(',', 'X').replace('.', ',').replace('X', '.')
                except ValueError:
                    pass
        
        return last_salary
    
    def convert_month_year_to_full_date(self, month_year: str) -> str:
        """Converte MM/YYYY para o último dia do mês"""
        import datetime
        
        match = re.match(r'(\d{2})/(\d{4})', month_year)
        if match:
            month = int(match.group(1))
            year = int(match.group(2))
            
            # Último dia do mês
            if month == 12:
                last_day = datetime.date(year + 1, 1, 1) - datetime.timedelta(days=1)
            else:
                last_day = datetime.date(year, month + 1, 1) - datetime.timedelta(days=1)
            
            return last_day.strftime('%d/%m/%Y')
        
        return month_year
    
    def process_cnis(self, pdf_path: str) -> Dict[str, Any]:
        """Processa o arquivo CNIS e extrai todos os dados"""
        try:
            logger.info(f"Processando arquivo: {pdf_path}")
            
            # Extrai texto do PDF
            text = self.extract_text_from_pdf(pdf_path)
            
            if not text.strip():
                return {
                    'success': False,
                    'error': 'Não foi possível extrair texto do PDF'
                }
            
            # Extrai dados
            personal_data = self.extract_personal_data(text)
            employment_data = self.extract_employment_data(text)
            
            result = {
                'success': True,
                'data': {
                    'dados_pessoais': personal_data,
                    'vinculos_empregaticios': employment_data,
                    'beneficios': []
                },
                'text_length': len(text)
            }
            
            logger.info(f"Extraídos {len(employment_data)} vínculos empregatícios")
            return result
            
        except Exception as e:
            logger.error(f"Erro no processamento: {e}")
            return {
                'success': False,
                'error': str(e)
            }

def main():
    """Função principal para execução via linha de comando"""
    parser = argparse.ArgumentParser(description='Extrator de dados do CNIS - Versão Simplificada')
    parser.add_argument('pdf_path', help='Caminho para o arquivo PDF do CNIS')
    parser.add_argument('--output', help='Arquivo de saída JSON (opcional)')
    
    args = parser.parse_args()
    
    # Verifica se o arquivo existe
    if not Path(args.pdf_path).exists():
        print(f"Erro: Arquivo não encontrado - {args.pdf_path}")
        sys.exit(1)
    
    # Processa o CNIS
    extractor = CNISExtractorSimple()
    result = extractor.process_cnis(args.pdf_path)
    
    # Saída
    if args.output:
        with open(args.output, 'w', encoding='utf-8') as f:
            json.dump(result, f, ensure_ascii=False, indent=2)
        print(f"Resultado salvo em: {args.output}")
    else:
        print(json.dumps(result, ensure_ascii=False, indent=2))

if __name__ == "__main__":
    main() 