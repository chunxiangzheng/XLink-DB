/**
 * functions and classes for spectrum viewer utility
 * @author Bruce Lab
 *
 */
import java.io.*;
import java.sql.Timestamp;
import java.util.*;

import org.systemsbiology.jrap.stax.MSXMLParser;
import org.systemsbiology.jrap.stax.Scan;
import org.systemsbiology.jrap.stax.ScanHeader;
public class MSExtractorForSpecViewer {
	public static void main(String[] args) {
		
	}
	
	public static void processInput(String in, String out) {// convert double quote to single quote
		try {
			FileReader fr = new FileReader(in);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(out);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				StringBuffer sb = new StringBuffer(line.length());
				for (int i = 0; i < line.length(); i++) {
					if (line.charAt(i) == '"') {
						sb.append('\'');
					} else {
						sb.append(line.charAt(i));
					}
				}
				ps.println(sb.toString());
				line = br.readLine();
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static void combineReact2FileGenerateXLinkDBInput( String react2Dir, String combine, String xlinkdbin) {
		File fdir = new File(react2Dir);
		File[] flist = fdir.listFiles();
		Set<String> cxlSet = new HashSet<String>();
		try {
			FileOutputStream fout = new FileOutputStream(combine);
			PrintStream ps = new PrintStream(fout);
			FileOutputStream fout1 = new FileOutputStream(xlinkdbin);
			PrintStream ps1 = new PrintStream(fout1);
			for (File f :flist) {
				String fname = f.getName();
				if (!fname.endsWith(".pep.xml.react2.xls")) continue;
				fname = fname.substring(0, fname.indexOf(".pep.xml.react2.xls"));
				FileReader fr = new FileReader(f);
				BufferedReader br = new BufferedReader(fr);
				String line = br.readLine();
				while (line != null) {
					if (line.startsWith("ms2 scan")) {
						line = br.readLine();
						continue;
					}
					ps.println(fname + "\t" + line);
					String[] arr = line.split("\t");
					System.out.println(arr[14]);
					String pepA = arr[10];                                      
					String pepB = arr[11];                                      
					String pepCode = pepA.compareTo(pepB) < 0 ? pepA + "-" + pepB : pepB + "-" + pepA;
					if (!cxlSet.contains(pepCode)) {
						cxlSet.add(pepCode);
						int kposA = getPos(arr[6]);                           
						int kposB = getPos(arr[7]);                             
						String proA = arr[14].split("\\|")[1];                  
						String proB = arr[15].split("\\|")[1];                 
						ps1.println(pepA + "\t" + proA + "\t" + kposA + "\t" + pepB + "\t" + proB + "\t" + kposB);
					}
					line = br.readLine();
				}
				br.close();
				fr.close();
			}
			ps1.close();
			fout1.close();
			ps.close();
			fout.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static int getPos(String s) {
		String[] arr = s.split("[325.13]");
		return getCleanLen(arr[0]) - 1;
	}
	public static int getCleanLen(String s) {
		int len = 0;
		for (int i = 0; i < s.length(); i++) {
			if (s.charAt(i) >='A' && s.charAt(i) <= 'Z') {
				len++;
			}
		}
		return len;
	}
	public static void combineReact2CSVFile(String dir, String out) {
		File fdir = new File(dir);
		File[] flist = fdir.listFiles();
		try {
			FileOutputStream fout = new FileOutputStream(dir + "/" + out);
			PrintStream ps = new PrintStream(fout);
			for (File f : flist) {
				String fname = f.getName();
				if (!fname.endsWith(".pep.xml.react2.xls")) continue;
				fname = fname.substring(5, fname.indexOf(".pep.xml.react2.xls"));    // need change
				FileReader fr = new FileReader(f);
				BufferedReader br = new BufferedReader(fr);
				String line = br.readLine();
				while (line != null) {
					ps.println(fname + "\t" + line);
					line = br.readLine();
				}
				br.close();
				fr.close();
			}			
			ps.close();
			fout.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static void getInputForSpecDB(String in, String out, String mzXMLDir) {
		try {
			FileReader fr = new FileReader(in);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(out);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");
				if (arr[1].equals("ms2 scan")) {
					line = br.readLine();
					continue;
				}
				String fname = arr[0];
				System.out.println(fname);
				int ms2ScanNum = Integer.valueOf(arr[1]);
				String pepAMod = arr[7];
				String pepBMod = arr[8];
				int ms3ScanNumA = Integer.valueOf(arr[13]);
				int ms3ScanNumB = Integer.valueOf(arr[14]);
				String proA = arr[15].split("\\|")[1];
				String proB = arr[16].split("\\|")[1];
				String pepAModCode = parseMods(pepAMod);
				String pepBModCode = parseMods(pepBMod);
				String pepA = arr[11];
				String pepB = arr[12];
				MSXMLParser parser = new MSXMLParser(mzXMLDir + "/" + fname + ".mzXML");
				Scan scan = parser.rap(ms2ScanNum);
				ScanHeader header = scan.getHeader();
				double ms2PrecMz = header.getPrecursorMz();
				int ms2PrecCharge = header.getPrecursorCharge();
				String ms2ScanSpec = "[";
				double[][] massList = scan.getMassIntensityList();
				for (int i = 0; i < massList[0].length; i++) {
					if (massList[1][i] != 0) ms2ScanSpec += "[" + massList[0][i] + "," + massList[1][i] + "],";
				}
				ms2ScanSpec += "]";
				scan = parser.rap(ms3ScanNumA);
				header = scan.getHeader();
				double ms3PrecMzA = header.getPrecursorMz();
				int ms3PrecChargeA = header.getPrecursorCharge();
				String ms3ScanSpecA = "[";
				massList = scan.getMassIntensityList();
				for (int i = 0; i < massList[0].length; i++) {
					if (massList[1][i] != 0) ms3ScanSpecA += "[" + massList[0][i] + "," + massList[1][i] + "],";
				}
				ms3ScanSpecA += "]";
				scan = parser.rap(ms3ScanNumB);
				header = scan.getHeader();
				double ms3PrecMzB = header.getPrecursorMz();
				int ms3PrecChargeB = header.getPrecursorCharge();

				String ms3ScanSpecB = "[";
				massList = scan.getMassIntensityList();
				for (int i = 0; i < massList[0].length; i++) {
					if (massList[1][i] != 0) ms3ScanSpecB += "[" + massList[0][i] + "," + massList[1][i] + "],";
				}
				ms3ScanSpecB += "]";
				int MS1ScanNum = ms2ScanNum - 1;
				double[][] MS1Peaklist = parser.rap(MS1ScanNum).getMassIntensityList();
				String MS1Spec = getScanPeakList(MS1Peaklist);
				String[] precPick = getSelectedPeaks(MS1Peaklist, ms2PrecMz, 20, ms2PrecCharge, "Precursor");
				String MS2PrecPeakSeries = precPick[0];
				String MS2PrecPeakAnnotation = precPick[1];
				double[][] MS2Peaklist = parser.rap(ms2ScanNum).getMassIntensityList();
				String[] pepAPick = getSelectedPeaks(MS2Peaklist, ms3PrecMzA, 20, ms3PrecChargeA, pepA);
				String[] pepBPick = getSelectedPeaks(MS2Peaklist, ms3PrecMzB, 20, ms3PrecChargeB, pepB);
				String pepAPeakSeries = pepAPick[0];
				String pepAPeakAnnotation = pepAPick[1];
				String pepBPeakSeries = pepBPick[0];
				String pepBPeakAnnotation = pepBPick[1];
				String reporterPeak = getCustomPeakSeries(MS2Peaklist, 752.415, 25);
				ps.println(fname + "\t" + pepA + "\t" + pepB + "\t" + proA + "\t" + proB + "\t" + pepAModCode + "\t" + pepBModCode + "\t" + ms2ScanNum
						+ "\t" + ms2PrecMz + "\t" + ms2PrecCharge + "\t" + ms2ScanSpec + "\t" + reporterPeak + "\t" + pepAPeakSeries + "\t" + pepBPeakSeries + "\t" 
						+ ms3ScanNumA + "\t" + ms3PrecMzA + "\t" + ms3PrecChargeA + "\t" + ms3ScanSpecA + "\t" + ms3ScanNumB + "\t" + ms3PrecMzB + "\t" + ms3PrecChargeB + "\t" 
						+ ms3ScanSpecB + "\t" + MS1Spec + "\t" + MS2PrecPeakSeries + "\t" + MS2PrecPeakAnnotation + "\t" + pepAPeakAnnotation + "\t" + pepBPeakAnnotation);
				line = br.readLine();
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static void getMS1ScanAndMS2PeakAssignment(String in, String out, String mzXMLdir) {//
		try {
			FileReader fr = new FileReader(in);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(out);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");
				String fname = arr[0];
				int MS2ScanNum = Integer.valueOf(arr[1]);
				int MS1ScanNum = MS2ScanNum - 1;
				String pepA = arr[11];
				String pepB = arr[12];
				int MS3ScanNumA = Integer.valueOf(arr[13]);
				int MS3ScanNumB = Integer.valueOf(arr[14]);
				MSXMLParser parser = new MSXMLParser(mzXMLdir + "/" + fname + ".mzXML");
				Scan MS2Scan = parser.rap(MS2ScanNum);
				Scan pepAScan = parser.rap(MS3ScanNumA);
				Scan pepBScan = parser.rap(MS3ScanNumB);
				double MS2PrecMz = MS2Scan.header.getPrecursorMz();
				double pepAMz = pepAScan.header.getPrecursorMz();
				double pepBMz = pepBScan.header.getPrecursorMz();
				int MS2PrecCharge = MS2Scan.header.getPrecursorCharge();
				int MS3PrecChargeA = pepAScan.header.getPrecursorCharge();
				int MS3PrecChargeB = pepBScan.header.getPrecursorCharge();
				double[][] MS1Peaklist = parser.rap(MS1ScanNum).getMassIntensityList();
				String MS1Spec = getScanPeakList(MS1Peaklist);
				String[] precPick = getSelectedPeaks(MS1Peaklist, MS2PrecMz, 20, MS2PrecCharge, "Precursor");
				String MS2PrecPeakSeries = precPick[0];
				String MS2PrecPeakAnnotation = precPick[1];
				double[][] MS2Peaklist = parser.rap(MS2ScanNum).getMassIntensityList();
				String[] pepAPick = getSelectedPeaks(MS2Peaklist, pepAMz, 20, MS3PrecChargeA, pepA);
				String[] pepBPick = getSelectedPeaks(MS2Peaklist, pepBMz, 20, MS3PrecChargeB, pepB);
				String pepAPeakSeries = pepAPick[0];
				String pepAPeakAnnotation = pepAPick[1];
				String pepBPeakSeries = pepBPick[0];
				String pepBPeakAnnotation = pepBPick[1];
				ps.println(MS1Spec + "\t" + MS2PrecPeakSeries + "\t" + MS2PrecPeakAnnotation + "\t" 
						+ pepAPeakSeries + "\t" + pepAPeakAnnotation + "\t" + pepBPeakSeries + "\t" + pepBPeakAnnotation);
				line = br.readLine();
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static String[] getSelectedPeaks(double[][] peaklist, double mz, int ppm, int charge, String seq) {
		String[] peakCode = new String[2];
		String peak = "[";
		String seqAnnotation = "[";
		int i;
		if (charge == 1) i = 2;
		else i = charge;
		for (; i > 0; i--) {
			double targetMz = (mz * charge - 1.007825 * charge + 1.007825 * i) / i;
			String customPeak = getCustomPeakSeries(peaklist, targetMz, ppm);
			if (!customPeak.equals("[]"))  {
				peak += customPeak + ",";
				seqAnnotation += "'" + seq + " " + i + "+',";
			}
		}
		peak = peak.substring(0, peak.length() - 1);
		peak += "]";
		seqAnnotation = seqAnnotation.substring(0, seqAnnotation.length() - 1);
		seqAnnotation += "]";
		peakCode[0] = peak;
		peakCode[1] = seqAnnotation;
		return peakCode;
	}
	public static String getScanPeakList(double[][] peaklist) {
		String scanText = "[";
		for (int i = 0; i < peaklist[0].length; i++) {
			if (peaklist[1][i] != 0) scanText += "[" + peaklist[0][i] + "," + peaklist[1][i] + "],";
		}
		scanText = scanText.substring(0, scanText.length() - 1);
		scanText += "]";
		return scanText;
	}
	public static void printScanHeaderInfor(String mzXML, int scanNum) {
		MSXMLParser parser = new MSXMLParser(mzXML);
		Scan scan = parser.rap(scanNum);
		ScanHeader header = scan.header;
		System.out.println("MS level is : " + header.getMsLevel());
		System.out.println("Precursor m/z is : " + header.getPrecursorMz());
		System.out.println("Precursor charge is :" + header.getPrecursorCharge());
	}
	public static void getReporterDaughterIon(String in, String out, String mzXMLDir) {
		try {
			FileReader fr = new FileReader(in);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(out);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");
				String fileName = arr[0];
				MSXMLParser parser = new MSXMLParser(mzXMLDir + "/" + fileName + ".mzXML");
				int scanNum = Integer.valueOf(arr[7]);
				Scan scan = parser.rap(scanNum);
				double[][] peaklist = scan.getMassIntensityList();
				double pepAMz = Double.valueOf(arr[12]);
				double pepBMz = Double.valueOf(arr[16]);
				String reporterPeak = getCustomPeakSeries(peaklist, 752.415, 25);
				String pepAPeak = getCustomPeakSeries(peaklist, pepAMz, 25);
				String pepBPeak = getCustomPeakSeries(peaklist, pepBMz, 25);
				if (pepAPeak.equals("[]")) {
					int scanNumPepA = Integer.valueOf(arr[11].trim());
					if (scanNumPepA - 2 > scanNum) {
						ScanHeader headerA = parser.rapHeader(scanNumPepA - 2);
						pepAPeak = getCustomPeakSeries(peaklist, headerA.getPrecursorMz(), 25);
					}
				}
				if (pepBPeak.equals("[]")) {
					int scanNumPepB = Integer.valueOf(arr[15].trim());
					if (scanNumPepB - 2 > scanNum) {
						ScanHeader headerB = parser.rapHeader(scanNumPepB - 2);
						pepBPeak = getCustomPeakSeries(peaklist, headerB.getPrecursorMz(), 25);						
					}
				}
				ps.println(reporterPeak + "\t" + pepAPeak + "\t" + pepBPeak);
				line = br.readLine();
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static String getCustomPeakSeries(double[][] peaklist, double mz, double ppm) {
		String peak = "[]";
		double lb = mz * (1 - ppm / 1000000);
		double ub = mz * (1 + ppm / 1000000);
		double maxIntensity = 0;		
		for (int i = 0; i < peaklist[0].length; i++) {
			if (peaklist[0][i] < lb) continue;
			if (peaklist[0][i] > ub) break;
			if (peaklist[1][i] > maxIntensity) {
				peak = "[" + peaklist[0][i] + "," + peaklist[1][i] + "]";
				maxIntensity = peaklist[1][i];
			}
		}
		
		return peak;
	}
	public static void extractScanToFile(String mzXML, int scanNum, String out) {
		MSXMLParser parser = new MSXMLParser(mzXML);
		Scan scan = parser.rap(scanNum);
		double[][] massList = scan.getMassIntensityList();
		try {
			FileOutputStream fout = new FileOutputStream(out);
			PrintStream ps = new PrintStream(fout);
			for (int i = 0; i < massList[0].length; i++) {
				if (massList[1][i] != 0) ps.println(massList[0][i] + "," + massList[1][i]);
			}
			ps.close();
			fout.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static void analyzeReactCSVTable_sayaka(String in, String out, String mzXMLDir) {
		//input format: filename \t ms2 scan \t ms2 query \t ms2 prec mass \t ms2 ppm \t ms2 mass1
		// \t ms2 mass2 \t ms3 pep1mods \t ms3 pep2mods \t ms3 fdr1 \t ms3 fdr2 \t ms3 pep1 \t ms3 pep2 \t ms3 scan1 \t ms3 scan2 \t ms3 prot1
		// \t ms3 prot2
		/**
		 * output format: filename \t pepA \t pepB \t proA \t proB \t modA \t modB \t ms2 scan \t ms2 prec mz \t ms2 prec charge 
		 * \t ms2 spec \t ms3 scan1 \t ms3 prec mz1 \t ms3 prec charge1 \t ms3 spec1 \t ms3 scan2 \t ms3 prec mz2 \t ms3 prec charge2 \t ms3 spec2
		 */
		try {
			FileReader fr = new FileReader(in);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(out);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				String[] arr = line.split("\t");
				String fname = arr[0];
				int ms2ScanNum = Integer.valueOf(arr[1]);
				String pepAMod = arr[7];
				String pepBMod = arr[8];
				int ms3ScanNumA = Integer.valueOf(arr[13]);
				int ms3ScanNumB = Integer.valueOf(arr[14]);
				String proA = arr[15].split("\\|")[1];
				String proB = arr[16].split("\\|")[1];
				String pepAModCode = parseMods(pepAMod);
				String pepBModCode = parseMods(pepBMod);
				String pepA = arr[11];
				String pepB = arr[12];
				MSXMLParser parser = new MSXMLParser(mzXMLDir + "/" + fname + ".mzXML");
				Scan scan = parser.rap(ms2ScanNum);
				ScanHeader header = scan.getHeader();
				double ms2PrecMz = header.getPrecursorMz();
				int ms2PrecCharge = header.getPrecursorCharge();
				String ms2ScanSpec = "[";
				double[][] massList = scan.getMassIntensityList();
				for (int i = 0; i < massList[0].length; i++) {
					if (massList[1][i] != 0) ms2ScanSpec += "[" + massList[0][i] + "," + massList[1][i] + "],";
				}
				ms2ScanSpec += "]";
				scan = parser.rap(ms3ScanNumA);
				header = scan.getHeader();
				double ms3PrecMzA = header.getPrecursorMz();
				int ms3PrecChargeA = header.getPrecursorCharge();
				String ms3ScanSpecA = "[";
				massList = scan.getMassIntensityList();
				for (int i = 0; i < massList[0].length; i++) {
					if (massList[1][i] != 0) ms3ScanSpecA += "[" + massList[0][i] + "," + massList[1][i] + "],";
				}
				ms3ScanSpecA += "]";
				scan = parser.rap(ms3ScanNumB);
				header = scan.getHeader();
				double ms3PrecMzB = header.getPrecursorMz();
				int ms3PrecChargeB = header.getPrecursorCharge();

				String ms3ScanSpecB = "[";
				massList = scan.getMassIntensityList();
				for (int i = 0; i < massList[0].length; i++) {
					if (massList[1][i] != 0) ms3ScanSpecB += "[" + massList[0][i] + "," + massList[1][i] + "],";
				}
				ms3ScanSpecB += "]";
				ps.println(fname + "\t" + pepA + "\t" + pepB + "\t" + proA + "\t" + proB + "\t" + pepAModCode + "\t" + pepBModCode + "\t" + ms2ScanNum
						+ "\t" + ms2PrecMz + "\t" + ms2PrecCharge + "\t" + ms2ScanSpec + "\t" + ms3ScanNumA + "\t" + ms3PrecMzA + "\t" + ms3PrecChargeA 
						+ "\t" + ms3ScanSpecA + "\t" + ms3ScanNumB + "\t" + ms3PrecMzB + "\t" + ms3PrecChargeB + "\t" + ms3ScanSpecB);
				line = br.readLine();
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static String parseMods(String in) {
		Map<Character, Double> aaMap = new HashMap<Character, Double>();
		aaMap.put('A', 71.08);
		aaMap.put('R', 156.19);
		aaMap.put('N', 114.11);
		aaMap.put('D', 115.09);
		aaMap.put('C', 103.15);
		aaMap.put('E', 129.12);
		aaMap.put('Q', 128.13);
		aaMap.put('G', 57.05);
		aaMap.put('H', 137.14);
		aaMap.put('O', 113.11);
		aaMap.put('I', 113.16);
		aaMap.put('L', 113.16);
		aaMap.put('K', 128.18);
		aaMap.put('M', 131.20);
		aaMap.put('F', 147.18);
		aaMap.put('P', 97.12);
		aaMap.put('U', 121.09);
		aaMap.put('S', 87.08);
		aaMap.put('T', 101.11);
		aaMap.put('W', 186.22);
		aaMap.put('Y', 163.18);
		aaMap.put('V', 99.13);
		String mods = "[";
		String[] arr = in.split("[\\[\\]]");
		int seqNum = 0;
		for (int i = 0; i < arr.length; i++) {
			boolean isNumber = false;
			if (arr[i].indexOf('.') != -1) isNumber = true;
			if (!isNumber) seqNum += arr[i].length();
			else {
				char aminoAcid = arr[i-1].charAt(arr[i-1].length() - 1);
				double modMass = Double.valueOf(arr[i]) - aaMap.get(aminoAcid);
				mods += "{index:" + seqNum +", modMass:" + modMass +", aminoAcid:'" + aminoAcid + "'},";
			}
		}
		mods += "]";
		return mods;
	}
}
