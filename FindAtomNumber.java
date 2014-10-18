/**
 * @author Bruce Lab Chunxiang Zheng 2012
 * Find atom number and site annotation in PDB file
 */
import java.io.*;
import java.util.*;
public class FindAtomNumber {
	private static final String PDBDIR = "pdb/";
	private static final String placeholder = "####";
	private static Map<String, String> aaMap;
	public static void main(String[] args) {
		if (args.length < 1) {
			System.out.println("java -jar FindAtomNumber.jar input");
			return;
		}
		aaMap = new HashMap<String, String>();
		aaMap.put("ALA", "A");
		aaMap.put("CYS", "C");
		aaMap.put("ASP", "D");
		aaMap.put("GLU", "E");
		aaMap.put("PHE", "F");
		aaMap.put("GLY", "G");
		aaMap.put("HIS", "H");
		aaMap.put("ILE", "I");
		aaMap.put("LYS", "K");
		aaMap.put("LEU", "L");
		aaMap.put("MET", "M");
		aaMap.put("ASN", "N");
		aaMap.put("PRO", "P");
		aaMap.put("GLN", "Q");
		aaMap.put("ARG", "R");
		aaMap.put("SER", "S");
		aaMap.put("THR", "T");
		aaMap.put("VAL", "V");
		aaMap.put("TRP", "W");
		aaMap.put("TYR", "Y");
		exportAtomNum(args[0],args[0] + "final");
	}
	public static void exportAtomNum(String input, String output) {                //output atom number
		try {
			FileReader fr = new FileReader(input);
			BufferedReader br = new BufferedReader(fr);
			FileOutputStream fout = new FileOutputStream(output);
			PrintStream ps = new PrintStream(fout);
			String l_input = br.readLine();
			while (l_input != null) {
				String[] pl_input = l_input.split("\t");
				String pdbA = pl_input[9];
				String pdbB = pl_input[20];
				String[] chainA_arr = pl_input[10].split("/");
				String[] chainB_arr = pl_input[21].split("/");
				String proseqA = pl_input[7];
				String proseqB = pl_input[18];
				int seqNumA = Integer.valueOf(pl_input[2]) + Integer.valueOf(pl_input[8]) + 1;
				int seqNumB = Integer.valueOf(pl_input[13]) + Integer.valueOf(pl_input[19]) + 1;
				String sitesA = "";
				String sitesB = "";
				String atomNumA = "";
				String atomNumB = "";
				if (pdbA.equals(placeholder) || pdbB.equals(placeholder) || !pdbA.equals(pdbB)) {      //no distance can be calculated
					if (pdbA.equals(placeholder) && pdbB.equals(placeholder)) {                        //no PDB structure for either protein
						sitesA = placeholder;
						sitesB = placeholder;
						atomNumA = placeholder;
						atomNumB = placeholder;
						writeLine(pl_input, sitesA, sitesB, atomNumA, atomNumB, placeholder, ps);
						l_input = br.readLine();
						continue;
					}
					if (pdbA.equals(placeholder)) {                                                   //no PDB structure for protein A
						sitesA = placeholder;
						atomNumA = placeholder;
						ArrayList<Coordinates> aCoordinates = retCoordinateArrayList(pdbB, seqNumB, proseqB, chainB_arr);
						String[] siteAtomNum = retFirstSiteAtomNum(aCoordinates);
						sitesB = siteAtomNum[0];
						atomNumB = siteAtomNum[1];
						writeLine(pl_input, sitesA, sitesB, atomNumA, atomNumB, placeholder, ps);
						l_input = br.readLine();
						continue;
					}
					if (pdbB.equals(placeholder)) {                                                 //no PDB structure for protein B
						sitesB = placeholder;
						atomNumB = placeholder;
						ArrayList<Coordinates> aCoordinates = retCoordinateArrayList(pdbA, seqNumA, proseqA, chainA_arr);
						String[] siteAtomNum = retFirstSiteAtomNum(aCoordinates);
						sitesA = siteAtomNum[0];
						atomNumA = siteAtomNum[1];
						writeLine(pl_input, sitesA, sitesB, atomNumA, atomNumB, placeholder, ps);
						l_input = br.readLine();
						continue;
					}
					// different structure for the two proteins
					ArrayList<Coordinates> aCoordinates = retCoordinateArrayList(pdbB, seqNumB, proseqB, chainB_arr);   
					for (Coordinates c : aCoordinates) {
						if (c.atomCode.equals(placeholder)) {
							sitesB = placeholder;
							atomNumB = placeholder;
						} else {
							sitesB = c.atomCode;
							atomNumB = String.valueOf(c.atomNum);
						}
					}
					aCoordinates = retCoordinateArrayList(pdbA, seqNumA, proseqA, chainA_arr);
					for (Coordinates c : aCoordinates) {
						if (c.atomCode.equals(placeholder)) {
							sitesA = placeholder;
							atomNumA = placeholder;
						} else {
							sitesA = c.atomCode;
							atomNumA = String.valueOf(c.atomNum);
						}
					}
					writeLine(pl_input, sitesA, sitesB, atomNumA, atomNumB, placeholder, ps);
					l_input = br.readLine();
					continue;
				}
				// Now a distance can be calculated
				String distance_str = "";
				int posA = Integer.valueOf(pl_input[2]) + Integer.valueOf(pl_input[8]) + 1;
				int posB = Integer.valueOf(pl_input[13]) + Integer.valueOf(pl_input[19]) + 1;
				ArrayList<Coordinates> aCoordA = retCoordinateArrayList(pdbA, posA, proseqA, chainA_arr);
				ArrayList<Coordinates> aCoordB = retCoordinateArrayList(pdbB, posB, proseqB, chainB_arr);
				//System.out.println(aCoordA.size() + "\t" + aCoordB.size());
				Coordinates[] coord = findClosest(aCoordA, aCoordB);
				distance_str = String.valueOf(calcDistance(coord[0], coord[1]));
				sitesA = coord[0].atomCode;
				if (sitesA == null) sitesA = "####";
				atomNumA = String.valueOf(coord[0].atomNum);
				if (coord[0].atomNum == 0) {
					atomNumA = "####";
					distance_str = "####";
				}
				sitesB = coord[1].atomCode;
				if (sitesB == null) sitesB = "####";
				atomNumB = String.valueOf(coord[1].atomNum);
				if (coord[1].atomNum == 0) {
					atomNumB = "####";
					distance_str = "####";
				}
				l_input = br.readLine();
				writeLine(pl_input, sitesA, sitesB, atomNumA, atomNumB, distance_str, ps);
			}			
			ps.close();
			fout.close();
			br.close();
			fr.close();
		} catch (IOException e) {
			System.err.println(e.getMessage());
		}
	}
	public static String[] retFirstSiteAtomNum(ArrayList<Coordinates> aCoordinates) {
		String[] siteAtomNum = new String[2];
		if (aCoordinates.size() == 0) {
			siteAtomNum[0] = placeholder;
			siteAtomNum[1] = placeholder;
			return siteAtomNum;
		}
		for (Coordinates c : aCoordinates) {
			if (c.atomCode.equals(placeholder)) {
				siteAtomNum[0] = placeholder;
				siteAtomNum[1] = placeholder;
			} else {
				siteAtomNum[0] = c.atomCode;
				siteAtomNum[1] = String.valueOf(c.atomNum);
			}
			break;
		}
		return siteAtomNum;
	}
	public static ArrayList<Coordinates> retCoordinateArrayList(String pdb, int seqNum, String seq, String[] al_chains) {   ///calculate shifts
		//System.out.println(pdb);
		ArrayList<Integer> shifts = checkPDB(pdb, seq, al_chains);
		ArrayList<Coordinates> aCoordinates = new ArrayList<Coordinates>();
		for (int j = 0; j < shifts.size(); j++) {
			try {
				FileReader fr = new FileReader(PDBDIR + pdb + ".pdb");
				BufferedReader br = new BufferedReader(fr);
				String l = br.readLine();
				while (l != null) {
					if(l.length() < 54){
						l = br.readLine();
						continue;
					}
					if(!l.substring(0, 6).equals("ATOM  ")) {
						l = br.readLine();
						continue;
					}
					String chainID = String.valueOf(l.charAt(21));
					int pos = seqNum + shifts.get(j);
					//System.out.println(l.substring(22, 26));
					if(Integer.valueOf(l.substring(22, 26).trim()) == pos 
							&& String.valueOf(l.charAt(21)).equals(al_chains[j])
							&& l.substring(12, 16).trim().equals("CA")) {
						Coordinates coord = new Coordinates();
						coord.atomCode = pos + ":" + chainID;
						coord.atomNum = Long.valueOf(l.substring(6, 11).trim());					
						coord.x = Double.valueOf(l.substring(30, 38).trim());
						coord.y = Double.valueOf(l.substring(38, 46).trim());
						coord.z = Double.valueOf(l.substring(46, 54).trim());
						aCoordinates.add(coord);
						break;
					}
					l = br.readLine();
				}
				br.close();
				fr.close();
			} catch (IOException e) {
				System.err.println(e.getMessage());
			}
			if (aCoordinates.size() == 0) {
				Coordinates coord = new Coordinates();
				coord.atomCode = placeholder;
				coord.atomNum = 9999;
				aCoordinates.add(coord);
			}
		}
		if (pdb.equals("2YRQ")) {
			System.out.println(shifts.get(0));
		}
		return aCoordinates;
	}
	public static void writeLine(String[] pl_input, String sitesA, String sitesB, String atomNumA, String atomNumB, String distance, PrintStream ps) {
		for (int i = 0; i < 10; i++) {
			ps.print(pl_input[i] + "\t");
		}
		ps.print(sitesA + "\t" + atomNumA + "\t");
		for (int i = 11; i < 21; i++) {
			ps.print(pl_input[i] + "\t");
		}
		ps.println(sitesB + "\t" + atomNumB + "\t" + distance);
	}
	public static ArrayList<Integer> checkPDB(String pdbCode, String seq, String[] chains) {
		ArrayList<Integer> shifts = new ArrayList<Integer>();
		for (int i = 0; i < chains.length; i++) {
			try {
				FileReader fr = new FileReader(PDBDIR + pdbCode + ".pdb");
				BufferedReader br = new BufferedReader(fr);
				String s = br.readLine();
				int pepCount = 0;
				String pep = "";
				int startPos = 0;
				while (s != null) {
					if(s.length() < 54) {
						s = br.readLine();
						continue;
					}
					if(!s.substring(0, 6).equals("ATOM  ")) {
						s = br.readLine();
						continue;
					}
					String chainID = String.valueOf(s.charAt(21));
					if (chainID.equals(chains[i]) && s.substring(12, 16).trim().equals("CA")) {
						if (Integer.valueOf(s.substring(22, 26).trim()) <= 0) {
							s = br.readLine();
							continue;
						}
						if (pepCount == 0) {
							startPos = Integer.valueOf(s.substring(22, 26).trim());
						}
						pep += aaMap.get(s.substring(17, 20));
						pepCount++;
						if (pepCount == 7) {
							//System.out.println(checkShift(pep, seq, startPos));
							shifts.add(checkShift(pep, seq, startPos));
							break;
						}
					}
					s = br.readLine();
				}
				br.close();
				fr.close();
			} catch (IOException e) {
				System.err.println(e.getMessage());
			}
		}
		return shifts;
	}
	public static int checkShift(String pep, String proseq, int startPos) {
		for (int i = startPos; i < proseq.length() - 6; i++) {
			int errorCount = 0;
			for (int j = 0; j < 7; j++) {
				//System.out.println(startPos + "\t" + i + "\t" + (i - 1 + j));
				if (pep.charAt(j) != proseq.charAt(i - 1 + j)) errorCount++;
			}
			if (errorCount < 2) {
				return startPos - i; 
			}
		}
		return 0;
	}
	public static Coordinates[] findClosest(ArrayList<Coordinates> aCoordA, ArrayList<Coordinates>aCoordB) {// find the closest possible distance
		Coordinates[] arr = new Coordinates[2];
		Coordinates a = new Coordinates();
		Coordinates b = new Coordinates();
		double distance = 9999;
		for(Coordinates cA : aCoordA) {
			for(Coordinates cB : aCoordB) {
				if(cB.atomCode.equals(cA.atomCode) || cA.atomCode.equals(placeholder) || cB.atomCode.equals(placeholder)) continue;
				double tmpDistance = calcDistance(cA, cB);
				if(tmpDistance < distance && tmpDistance > 0) {
					a = cA;
					b = cB;
					distance = tmpDistance;
				}
			}
		}
		arr[0] = a;
		arr[1] = b;
		return arr;
	}
	public static double calcDistance(Coordinates coordA, Coordinates coordB) {                           //calculate distance
		double distance;
		double distanceSqr = (coordA.x - coordB.x) * (coordA.x - coordB.x) + (coordA.y - coordB.y) * (coordA.y - coordB.y) + (coordA.z - coordB.z) * (coordA.z - coordB.z);
		distance = Math.sqrt(distanceSqr);
		return distance;
	}
}	
class Coordinates {
	public String atomCode;
	public long atomNum;
	public double x, y, z;
	public Coordinates() {
		x = -1;
		y = -1;
		z = -1;
	}
}
