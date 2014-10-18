
/**
 * 
 * @author Bruce Lab
 * Find best PDB for each cross-linking pair
 *
 */
import java.io.*;
import java.util.*;
public class FindPDBCode {
	private static final String uniprotFileDirectory = "uniprot/"; // the directory where the uniprot files were stored
	public static void main(String[] args) {
		if (args.length<1) {
			System.out.println("java -jar FindPDBCode.jar datatble");
			return;
		}
		findPDBCode("recycled/" + args[0], "recycled/" + args[0] + "pdb", "recycled/" + args[0] + "uniquePDB");
		
	}
	public static void findPDBCode(String input, String output, String uniquePDB) {           //find the best PDB code
		String type = "intra"; 
		Set<String> pdbSet = new HashSet<String>();
		try {
			FileReader fr = new FileReader(input);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(output);
			PrintStream ps = new PrintStream(fout);
			String line = br.readLine();
			while (line != null) {
				String[] parsedLine = line.split("\t");
				if (!parsedLine[1].equals(parsedLine[10])) type = "hetero";
				else {
					int beginA = Integer.valueOf(parsedLine[8]);
					int endA = beginA + parsedLine[0].length() - 1;
					int beginB = Integer.valueOf(parsedLine[17]);
					int endB = beginB + parsedLine[9].length() - 1;
					if (beginA <= endB && beginB <= endA) type = "homo";
				}
				int posA = Integer.valueOf(parsedLine[8]) + Integer.valueOf(parsedLine[2]);
				int posB = Integer.valueOf(parsedLine[17]) + Integer.valueOf(parsedLine[11]);
				ArrayList<PDB> aPDBA = extractPDBcode(parsedLine[1], posA);
			    ArrayList<PDB> aPDBB = extractPDBcode(parsedLine[10], posB);
				String pdbs = findBestPDBStructure(aPDBA, aPDBB, type);
				//System.out.println(pdbs + "\t" + type);
				String pdbA = "";
				String pdbB = "";
				String chainA = "";
				String chainB = "";
				if (pdbs.indexOf(':') == -1) System.out.println(pdbs);
				String[] arr = pdbs.split(":");
				pdbA = arr[0].substring(0, arr[0].indexOf('-'));
				chainA = arr[0].substring(arr[0].indexOf('-') + 1);
				pdbB = arr[1].substring(0, arr[1].indexOf('-'));
				chainB = arr[1].substring(arr[1].indexOf('-') + 1);
				pdbSet.add(pdbA);
				pdbSet.add(pdbB);
				for(int i = 0; i < 9; i++) ps.print(parsedLine[i] + "\t");
				ps.print(pdbA + "\t" + chainA + "\t");
				for(int i = 9; i < 18; i++) ps.print(parsedLine[i] + "\t");
				ps.println(pdbB + "\t" + chainB);
				line = br.readLine();
			}
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
		try {
			FileOutputStream fout = new FileOutputStream(uniquePDB);
			PrintStream ps = new PrintStream(fout);
			String[] arr = pdbSet.toArray(new String[0]);
			for(String s : arr) {
				if(s.equals("####")) continue;
				ps.println(s);
			}
			ps.close();
			fout.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	// Generate cxl arraylist containing all the pdb code for each protein, the input is from the batch download input
	public static ArrayList<PDB> extractPDBcode(String uniprot, int pos) {
		ArrayList<PDB> aPDB = new ArrayList<PDB>();
		try {
			FileReader fr = new FileReader(uniprotFileDirectory + uniprot + ".txt");
			BufferedReader br = new BufferedReader(fr);
			String line = br.readLine();
			while (line != null) {
				if (line.length() < 10) {
					line = br.readLine();
					continue;
				}
				if (line.substring(0, 9).equals("DR   PDB;")) {
					PDB p = new PDB();
					aPDB.add(p);
					String[] pl = line.split(";");
					p.pdbCode = pl[1].trim(); 
					if (pl[3].equals(" -")) p.resolution = 999; // when only - appears, the resolution is unknown, here we use 999
					else p.resolution = Double.valueOf(pl[3].substring(0, 4));
					retChain(pl[4], p);
				}
				line = br.readLine();
			}
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
		ArrayList<PDB> finalPDB = new ArrayList<PDB>();
		for (PDB p : aPDB) {
			boolean isCovered = false;
			for (Chain ch : p.chainID) {
				if (ch.begin - 1 <= pos && ch.end  - 1 >= pos) {
					isCovered = true;
					break;
				}
			}
			if (isCovered) finalPDB.add(p);
		}
		return finalPDB;
	}
	/*
	 * find chain ID and sequence coverage
	 */
	public static void retChain(String s, PDB p) {
		ArrayList<Chain> aChain = new ArrayList<Chain>();
		s = s.substring(0, s.length() - 1).trim();
		String[] parsedS = s.split(",");
		for (String str : parsedS) {
			str = str.trim();
			int begin = 0;
			int end = 0;
			if (!str.substring(str.indexOf('=') + 1, str.indexOf('-')).equals("")) {
				begin = Integer.valueOf(str.substring(str.indexOf('=') + 1, str.indexOf('-')));
			}
			if (!str.substring(str.indexOf('-') + 1, str.length()).equals("")) {
				end = Integer.valueOf(str.substring(str.indexOf('-') + 1, str.length()));
			}
			for (int i = 0; i <str.indexOf('='); i++) {
				char c = str.charAt(i);
				if (c!= '/') {
					Chain ch = new Chain();
					ch.id = c;
					ch.begin = begin;
					ch.end = end;
					aChain.add(ch);
				}
			}
		}
		p.chainID = aChain;
	}
	/*
	 * search for the best structure file to use for each type.
	 * if intra, the best file is the highest res and highest seq cover, seq cover is weighted more than the res
	 * if inter, but homo, the file is chosen from pdb who has more than one chain
	 * if hetero, find the common pdb first, than find the best for each one
	 */
	public static String findBestPDBStructure(ArrayList<PDB> aPDBA, ArrayList<PDB>aPDBB, String type) {
		ArrayList<PDB> candidate = new ArrayList<PDB>();
		String result = "";
		Map<String, PDB>pdbMapA = new HashMap<String, PDB>();
		Map<String, PDB>pdbMapB = new HashMap<String, PDB>();
		for(PDB p : aPDBA)pdbMapA.put(p.pdbCode, p);
		for(PDB p : aPDBB)pdbMapB.put(p.pdbCode, p);
		if(type.equals("hetero")) {
			boolean hasCocrystal = false;
			for(PDB p1 : aPDBA) {
				for(PDB p2 : aPDBB) {
					if(p1.pdbCode.equals(p2.pdbCode) && !p1.pdbCode.equals("####")) {
						candidate.add(p1);
						hasCocrystal = true;
					}
				}
			}
			if(hasCocrystal) {
				String bestpdb = findBest(candidate);
				PDB pdbA = pdbMapA.get(bestpdb);
				PDB pdbB = pdbMapB.get(bestpdb);
				result = bestpdb + "-";
				for (int i = 0; i < pdbA.chainID.size(); i++) {
					result += String.valueOf(pdbA.chainID.get(i).id) + "/";
				}
				result += ":" + bestpdb + "-";
				for (int i = 0; i < pdbB.chainID.size(); i++) {
					result += String.valueOf(pdbB.chainID.get(i).id) + "/";
				}
				return result;
			} else {
				String pA = "";
				if (aPDBA.isEmpty()) {
					pA = "####-####";
				} else {
					String bestpdb = findBest(aPDBA);
					pA = printBestPDB(bestpdb, pdbMapA);
				}
				String pB = "";
				if (aPDBB.isEmpty()) {
					pB = "####-####";
				} else {
					String bestpdb = findBest(aPDBB);
					pB = printBestPDB(bestpdb, pdbMapB); 
				}
				result = pA + ":" + pB;
				return result;
			}
		}
		if(type.equals("homo")) {
			boolean hasOligomer = false;
			for(PDB p1 : aPDBA) {
				for (PDB p2 : aPDBB) {
					if (p1.pdbCode.equals(p2.pdbCode) && p1.chainID.size() > 1 && !p1.pdbCode.equals("####")) {
						candidate.add(p1);
						hasOligomer = true;
					}
				}
			}
			if(hasOligomer) {
				String bestpdb = findBest(candidate);
				result = printBestPDB(bestpdb, pdbMapA) + ":" + printBestPDB(bestpdb, pdbMapB);
				return result;
			} else {
				String pA = "";
				if (aPDBA.isEmpty()) {
					pA = "####-####";
				} else {
					String bestpdb = findBest(aPDBA);
					pA = printBestPDB(bestpdb, pdbMapA);
				}
				String pB = "";
				if (aPDBB.isEmpty()) {
					pB = "####-####";
				} else {
					String bestpdb = findBest(aPDBB);
					pB = printBestPDB(bestpdb, pdbMapB);
				}
				result = pA + ":" + pB;
				return result;
			}
		}
		return "####-####:####-####";
	}
	public static String findBest(ArrayList<PDB> aPDB) {
		String bestPDB = "####";
		int bestCoverage = 0;
		for(PDB p : aPDB) {
			int tmp = 0; 
			for (Chain c : p.chainID) {
				if (c.end - c.begin + 1 > tmp) {
					tmp = c.end - c.begin + 1;
				}
			}
			p.coverage = tmp;
			if(p.coverage > bestCoverage){
				bestPDB = p.pdbCode;
				bestCoverage = p.coverage;
			}			
		}
		return bestPDB;
	}
	public static String printBestPDB(String pdb, Map<String, PDB>map) {
		PDB p = map.get(pdb);
		String tmp = pdb + "-";
		if(p.chainID.size() == 0) tmp += "####";
		else for(int i = 0; i < p.chainID.size(); i++){
				tmp += p.chainID.get(i).id + "/";
			}
		return tmp;
	}
}

