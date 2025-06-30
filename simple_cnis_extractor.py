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
        
        # Padrões para CPF - melhorados
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
        
        # Padrões para nome - melhorados para extrair corretamente
        nome_patterns = [
            # Padrão específico do CNIS: NIT + CPF + Nome na mesma linha
            r'NIT[:\s]*[\d\.-]+\s+CPF[:\s]*\d{3}\.\d{3}\.\d{3}-\d{2}\s+Nome[:\s]*([A-ZÁÊÇÕ][A-ZÁÊÇÕa-záêçõ\s]+?)(?:\s+Data|$)',
            # Padrão: Nome após CPF na mesma linha
            r'CPF[:\s]*\d{3}\.\d{3}\.\d{3}-\d{2}\s+Nome[:\s]*([A-ZÁÊÇÕ][A-ZÁÊÇÕa-záêçõ\s]+?)(?:\s+Data|$)',
            # Padrão: Nome em linha específica com "Nome:"
            r'Nome[:\s]+([A-ZÁÊÇÕ][A-ZÁÊÇÕa-záêçõ\s]+?)(?:\s+Data|$)',
            # Padrão: linha que parece ser nome completo (pelo menos 2 palavras, maiúsculas)
            r'^([A-ZÁÊÇÕ][A-ZÁÊÇÕa-záêçõ]+\s+[A-ZÁÊÇÕa-záêçõ\s]+)\s*$'
        ]
        
        # Primeiro tenta encontrar o padrão específico do CNIS na linha completa
        full_text_match = re.search(r'NIT[:\s]*[\d\.-]+\s+CPF[:\s]*\d{3}\.\d{3}\.\d{3}-\d{2}\s+Nome[:\s]*([A-ZÁÊÇÕ][A-ZÁÊÇÕa-záêçõ\s]+?)(?:\s+Data|$)', text, re.IGNORECASE)
        if full_text_match:
            nome = full_text_match.group(1).strip()
            # Limpa o nome
            nome = re.sub(r'[0-9\-\_\.\(\)\[\]]', '', nome)
            nome = re.sub(r'\s+', ' ', nome)
            nome = nome.strip()
            if len(nome) > 5 and ' ' in nome:
                personal_data['nome'] = nome.title()
        
        # Se não encontrou, tenta os outros padrões linha por linha
        if 'nome' not in personal_data:
            lines = text.split('\n')
            for line in lines:
                line = line.strip()
                for pattern in nome_patterns:
                    match = re.search(pattern, line, re.MULTILINE | re.IGNORECASE)
                    if match:
                        nome = match.group(1).strip()
                        # Limpa o nome
                        nome = re.sub(r'[0-9\-\_\.\(\)\[\]]', '', nome)
                        nome = re.sub(r'\s+', ' ', nome)  # Remove espaços duplos
                        nome = nome.strip()
                        
                        # Validações do nome
                        if (len(nome) > 5 and 
                            not nome.isdigit() and 
                            ' ' in nome and  # Deve ter pelo menos nome e sobrenome
                            not re.match(r'^(DATA|NASCIMENTO|CPF|NIT|EXTRATO)', nome.upper())):
                            personal_data['nome'] = nome.title()
                            break
                if 'nome' in personal_data:
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
        
        for i, line in enumerate(lines):
            line = line.strip()
            
            # Identifica início de uma seção de vínculo (padrões melhorados)
            is_start_of_section = (
                re.match(r'^Código Emp\.', line) or 
                re.match(r'^\d+\s+\d{2}\.\d{3}\.\d{3}(/\d{4}-\d{2})?', line) or
                re.match(r'^\d+\s+AGRUPAMENTO', line) or
                # Novo padrão: linha que começa com número seguido de CNPJ
                re.match(r'^\d+\s+\d{2}\.\d{3}\.\d{3}', line) or
                # Padrão para seções que podem ter CNPJ na linha seguinte
                (re.match(r'^\d+\s+[A-Z]', line) and i + 1 < len(lines) and 
                 re.search(r'\d{2}\.\d{3}\.\d{3}', lines[i + 1]))
            )
            
            if is_start_of_section:
                # Salva a seção anterior se existir
                if current_section:
                    sections.append('\n'.join(current_section))
                
                current_section = [line]
                in_employment_section = True
                
                # Se o CNPJ está na próxima linha, inclui ela também
                if (i + 1 < len(lines) and 
                    not re.search(r'\d{2}\.\d{3}\.\d{3}', line) and
                    re.search(r'\d{2}\.\d{3}\.\d{3}', lines[i + 1])):
                    current_section.append(lines[i + 1].strip())
                    
            elif in_employment_section:
                current_section.append(line)
                
                # Identifica fim da seção (padrões melhorados)
                is_end_of_section = (
                    re.match(r'^Relações Previdenciárias', line) or
                    re.match(r'^Valores Consolidados', line) or
                    re.match(r'^Legenda', line) or
                    re.match(r'^TOTAIS', line) or
                    # Nova seção começando
                    (re.match(r'^\d+\s+\d{2}\.\d{3}\.\d{3}', line) and len(current_section) > 5) or
                    (re.match(r'^\d+\s+AGRUPAMENTO', line) and len(current_section) > 5) or
                    # Linha vazia seguida de padrão de nova seção
                    (not line and i + 1 < len(lines) and 
                     re.match(r'^\d+\s+(\d{2}\.\d{3}\.\d{3}|AGRUPAMENTO)', lines[i + 1].strip()))
                )
                
                if is_end_of_section:
                    if re.match(r'^\d+\s+(\d{2}\.\d{3}\.\d{3}|AGRUPAMENTO)', line):
                        # Nova seção começando, remove esta linha da atual
                        current_section.pop()
                        sections.append('\n'.join(current_section))
                        current_section = [line]
                    else:
                        sections.append('\n'.join(current_section))
                        current_section = []
                        in_employment_section = False
            
            # Se não está em seção mas encontra linha com CNPJ isolado, pode ser início de seção
            elif not in_employment_section and re.match(r'^\d{2}\.\d{3}\.\d{3}(/\d{4}-\d{2})?', line):
                # Verifica se há contexto de empregador nas linhas anteriores ou seguintes
                has_context = False
                for j in range(max(0, i-2), min(len(lines), i+3)):
                    if j != i and re.search(r'[A-Z]{3,}', lines[j]):
                        has_context = True
                        break
                
                if has_context:
                    current_section = [line]
                    in_employment_section = True
        
        # Adiciona a última seção se existir
        if current_section:
            sections.append('\n'.join(current_section))
        
        # Filtra seções muito pequenas que provavelmente não são vínculos
        filtered_sections = []
        for section in sections:
            section_lines = [l.strip() for l in section.split('\n') if l.strip()]
            # Mantém seções que tenham pelo menos CNPJ ou nome de empresa
            if (len(section_lines) >= 2 and 
                (re.search(r'\d{2}\.\d{3}\.\d{3}', section) or 
                 re.search(r'[A-Z]{5,}', section) or
                 re.search(r'AGRUPAMENTO', section))):
                filtered_sections.append(section)
        
        return filtered_sections
    
    def extract_employment_from_section(self, section: str) -> Optional[Dict[str, str]]:
        """Extrai dados de um vínculo empregatício de uma seção"""
        employment = {
            'empregador': '',
            'cnpj': '',
            'data_inicio': '',
            'data_fim': ''
        }
        
        lines = section.split('\n')
        full_text = ' '.join(lines)  # Junta tudo para tratar CNPJ em múltiplas linhas
        
        # Extrai CNPJ completo primeiro (pode estar em múltiplas linhas)
        cnpj_patterns = [
            r'(\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2})',  # CNPJ completo
            r'(\d{2}\.\d{3}\.\d{3})',              # CNPJ incompleto
        ]
        
        for pattern in cnpj_patterns:
            match = re.search(pattern, full_text)
            if match:
                employment['cnpj'] = match.group(1)
                break
        
        # Extrai empregador - melhorado para lidar com múltiplas linhas
        empregador_found = False
        for i, line in enumerate(lines):
            line = line.strip()
            
            # Padrão: linha com código + CNPJ + nome do empregador
            empregador_match = re.match(r'^\d+\s+(\d{2}\.\d{3}\.\d{3}(/\d{4}-\d{2})?)\s+(.+)$', line)
            if empregador_match:
                if not employment['cnpj']:
                    employment['cnpj'] = empregador_match.group(1)
                empregador = empregador_match.group(3).strip()
                
                # Coleta linhas subsequentes que podem ser continuação do nome
                for j in range(i + 1, min(i + 4, len(lines))):  # Máximo 3 linhas adicionais
                    next_line = lines[j].strip()
                    
                    # Para se encontrar padrão de data, número ou palavra-chave
                    if (re.match(r'^\d{2}/\d{2}/\d{4}', next_line) or 
                        re.match(r'^\d+', next_line) or
                        re.search(r'(Empregado|Contribuinte|Data|Início|Fim|Remuneração)', next_line, re.IGNORECASE) or
                        len(next_line) < 3):
                        break
                    
                    # Se a linha parece ser continuação do nome (sem números no início)
                    if not re.match(r'^\d', next_line) and len(next_line) > 2:
                        empregador += ' ' + next_line
                    else:
                        break
                
                # Limpa o nome do empregador
                empregador = re.sub(r'\s*(Empregado ou Agente|Contribuinte Individual).*$', '', empregador, flags=re.IGNORECASE)
                empregador = re.sub(r'\d{2}\.\d{3}\.\d{3}(/\d{4}-\d{2})?', '', empregador)
                empregador = re.sub(r'\s+', ' ', empregador.strip())  # Remove espaços duplos
                employment['empregador'] = empregador
                empregador_found = True
                break
            
            # Padrão para AGRUPAMENTO
            agrupamento_match = re.match(r'^\d+\s+(AGRUPAMENTO.+)$', line)
            if agrupamento_match:
                empregador = agrupamento_match.group(1).strip()
                
                # Coleta linhas subsequentes para AGRUPAMENTO também
                for j in range(i + 1, min(i + 3, len(lines))):
                    next_line = lines[j].strip()
                    if (re.match(r'^\d{2}/\d{2}/\d{4}', next_line) or 
                        re.match(r'^\d+', next_line) or
                        re.search(r'(Empregado|Contribuinte|Data)', next_line, re.IGNORECASE)):
                        break
                    if not re.match(r'^\d', next_line) and len(next_line) > 2:
                        empregador += ' ' + next_line
                    else:
                        break
                
                empregador = re.sub(r'\s*(Empregado ou Agente|Contribuinte Individual).*$', '', empregador, flags=re.IGNORECASE)
                empregador = re.sub(r'\d{2}\.\d{3}\.\d{3}(/\d{4}-\d{2})?', '', empregador)
                empregador = re.sub(r'\s+', ' ', empregador.strip())
                employment['empregador'] = empregador
                empregador_found = True
                break
        
        # Se não encontrou o empregador pelos padrões acima, tenta buscar por texto livre
        if not empregador_found and employment['cnpj']:
            # Busca texto após CNPJ na mesma linha ou linhas subsequentes
            cnpj_pattern = re.escape(employment['cnpj'])
            for i, line in enumerate(lines):
                if re.search(cnpj_pattern, line):
                    # Remove CNPJ e números da linha
                    clean_line = re.sub(r'\d{2}\.\d{3}\.\d{3}(/\d{4}-\d{2})?', '', line)
                    clean_line = re.sub(r'^\d+\s*', '', clean_line)
                    clean_line = clean_line.strip()
                    
                    if len(clean_line) > 3:
                        empregador = clean_line
                        
                        # Adiciona linhas subsequentes se necessário
                        for j in range(i + 1, min(i + 3, len(lines))):
                            next_line = lines[j].strip()
                            if (re.match(r'^\d{2}/\d{2}/\d{4}', next_line) or 
                                re.match(r'^\d+', next_line) or
                                re.search(r'(Empregado|Contribuinte|Data)', next_line, re.IGNORECASE)):
                                break
                            if not re.match(r'^\d', next_line) and len(next_line) > 2:
                                empregador += ' ' + next_line
                            else:
                                break
                        
                        empregador = re.sub(r'\s*(Empregado ou Agente|Contribuinte Individual).*$', '', empregador, flags=re.IGNORECASE)
                        empregador = re.sub(r'\s+', ' ', empregador.strip())
                        employment['empregador'] = empregador
                        break
        
        # Extrai datas - melhorado para evitar confundir com data de nascimento
        data_nascimento = None
        
        # Primeiro, identifica a data de nascimento se presente
        for line in lines:
            nasc_match = re.search(r'(Data de nascimento|Nascimento)[:\s]*(\d{2}/\d{2}/\d{4})', line, re.IGNORECASE)
            if nasc_match:
                data_nascimento = nasc_match.group(2)
                break
        
        # Agora extrai datas de vínculo, excluindo a data de nascimento
        dates_found = []
        for line in lines:
            line = line.strip()
            
            # Pula linhas que claramente são sobre nascimento
            if re.search(r'(nascimento|nasc\.)', line, re.IGNORECASE):
                continue
            
            # Encontra todas as datas na linha
            date_matches = re.findall(r'\d{2}/\d{2}/\d{4}', line)
            for date in date_matches:
                if data_nascimento and date == data_nascimento:
                    continue
                if date not in dates_found:
                    dates_found.append(date)
            
            # Também procura por padrões MM/YYYY
            month_year_matches = re.findall(r'\d{2}/\d{4}', line)
            for month_year in month_year_matches:
                # Valida se é uma data válida antes de converter
                month_match = re.match(r'(\d{2})/(\d{4})', month_year)
                if month_match:
                    month = int(month_match.group(1))
                    year = int(month_match.group(2))
                    # Só aceita meses válidos e anos razoáveis
                    if 1 <= month <= 12 and 1900 <= year <= 2100:
                        full_date = self.convert_month_year_to_full_date(month_year)
                        if full_date not in dates_found:
                            dates_found.append(full_date)
        
        # Atribui as datas encontradas
        if len(dates_found) >= 2:
            employment['data_inicio'] = dates_found[0]
            employment['data_fim'] = dates_found[1]
        elif len(dates_found) == 1:
            employment['data_inicio'] = dates_found[0]
            employment['data_fim'] = ''
        
        # Se não encontrou datas pelo método acima, tenta padrões específicos
        if not employment['data_inicio']:
            for line in lines:
                line = line.strip()
                
                # Padrões de datas de vínculo
                date_patterns = [
                    (r'(\d{2}/\d{2}/\d{4})\s+(\d{2}/\d{2}/\d{4})', 2),  # Duas datas completas
                    (r'(\d{2}/\d{2}/\d{4})\s+(\d{2}/\d{4})', 1),       # Data completa + MM/YYYY
                    (r'(\d{2}/\d{2}/\d{4})', 0)                        # Uma data
                ]
                
                for pattern, date_count in date_patterns:
                    matches = re.findall(pattern, line)
                    for match in matches:
                        if isinstance(match, tuple):
                            date1 = str(match[0]) if date_count >= 0 else str(match)
                            date2 = str(match[1]) if date_count > 0 and len(match) > 1 else None
                        else:
                            date1 = str(match)
                            date2 = None
                        
                        # Ignora se for a data de nascimento
                        if data_nascimento and (date1 == data_nascimento or (date2 and date2 == data_nascimento)):
                            continue
                        
                        if date_count == 2 and date2:
                            employment['data_inicio'] = date1
                            employment['data_fim'] = date2
                            break
                        elif date_count == 1 and date2:
                            employment['data_inicio'] = date1
                            employment['data_fim'] = self.convert_month_year_to_full_date(date2)
                            break
                        else:
                            if not employment['data_inicio']:
                                employment['data_inicio'] = date1
                            elif not employment['data_fim'] and date1 != employment['data_inicio']:
                                employment['data_fim'] = date1
                    
                    if employment['data_inicio']:
                        break
        
        # Se não encontrou data de fim, deixa vazio (indica vínculo ativo)
        if not employment['data_fim']:
            employment['data_fim'] = ''
        
        return employment if employment['empregador'] else None
    
    def convert_month_year_to_full_date(self, month_year: str) -> str:
        """Converte MM/YYYY para o último dia do mês"""
        import datetime
        
        match = re.match(r'(\d{2})/(\d{4})', month_year)
        if match:
            try:
                month = int(match.group(1))
                year = int(match.group(2))
                
                # Validação do mês
                if month < 1 or month > 12:
                    logger.warning(f"Mês inválido encontrado: {month} em {month_year}")
                    return month_year  # Retorna o valor original se inválido
                
                # Último dia do mês
                if month == 12:
                    last_day = datetime.date(year + 1, 1, 1) - datetime.timedelta(days=1)
                else:
                    last_day = datetime.date(year, month + 1, 1) - datetime.timedelta(days=1)
                
                return last_day.strftime('%d/%m/%Y')
            except (ValueError, OverflowError) as e:
                logger.warning(f"Erro ao converter data {month_year}: {e}")
                return month_year
        
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
            
            # Mapeia os dados para o formato esperado
            result_data = {
                'client_name': personal_data.get('nome', ''),
                'client_cpf': personal_data.get('cpf', ''),
                'vinculos_empregaticios': employment_data
            }
            
            result = {
                'success': True,
                'data': result_data,
                'text_length': len(text)
            }
            
            logger.info(f"Extraídos {len(employment_data)} vínculos empregatícios")
            logger.info(f"Nome do cliente: {result_data['client_name']}")
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