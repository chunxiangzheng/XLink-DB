/**
 * @author Bruce Lab Chunxiang Zheng 2012
 * Extract uniprot ID from the input file
 */
import java.util.*;
import java.io.*;
public class ReactOutputAnalysis {
	public static void main(String[] args) {
		if (args.length < 1) {;
			System.out.println("java -jar filename");
		}
		String[] p = args[0].split("\\/");
		String fname = p[p.length - 1];
		retUniqueID("recycled/"+fname,"recycled/tmp"+fname+"uniqueUniprot");
	}

	public static void retUniqueID(String input, String output) {
		Set<String> proSet = new HashSet<String>();
		ArrayList<String> proID = new ArrayList<String>();
		try {
			FileReader fr = new FileReader(input);
			BufferedReader br = new BufferedReader(fr);
			String l = br.readLine();
			while (l != null) {
				String[] pl = l.split("\t");
				if (!proSet.contains(pl[1])) {
					proSet.add(pl[1]);
					proID.add(pl[1]);
				}
				if (!proSet.contains(pl[4])) {
					proSet.add(pl[4]);
					proID.add(pl[4]);
				}
				l = br.readLine();
			}
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
		try {
			FileOutputStream fout = new FileOutputStream(output);
			PrintStream ps = new PrintStream(fout);
			for (String s : proID) ps.println(s);
			ps.close();
			fout.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
}
class CXL {
	public String pepA, pepB, proA, proB, pdbA, pdbB;
	public int kposA, kposB;
	public ArrayList<PDB> aPDBA, aPDBB;
	public CXL() {
		aPDBA = new ArrayList<PDB>();
		aPDBB = new ArrayList<PDB>();
	}
	@Override
	public boolean equals(Object o) {
		CXL c = (CXL) o;
		String s, t;
		if (pepA.compareTo(pepB) >= 0) s = pepA + pepB;
		else s = pepB + pepA; 
		if (c.pepA.compareTo(c.pepB) >= 0) t = c.pepA + c.pepB;
		else t = c.pepB + c.pepA;
		return s.equals(t);
	}
	@Override
	public int hashCode() {
		String s;
		int code = 0;
		if (pepA.compareTo(pepB) >= 0) s = pepA + pepB;
		else s = pepB + pepA;
		for (int i = 0; i < s.length(); i++) {
			code = code * 31 + Integer.valueOf(s.charAt(i));
		}
		return code;
	}
	
}
class PDB {
	public String pdbCode;
	public ArrayList<Chain> chainID;
	public double resolution;
	public int coverage;
	public PDB() {
		chainID = new ArrayList<Chain>();
		coverage = 0;
	}
}
class Chain {
	public char id;
	public int begin, end;
	public Chain() {
		
	}
}
